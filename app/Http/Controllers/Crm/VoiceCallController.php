<?php

namespace App\Http\Controllers\Crm;

use App\CrmEmail;
use App\Http\Controllers\Controller;
use App\Services\VoiceCallService;
use App\VoiceCallQueue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Sara (ElevenLabs) voice calls from inside the CRM: manual calls, per-lead follow-up
 * calls, a bulk follow-up queue (worked one-at-a-time by voice:process-queue via cron),
 * recent calls + transcripts, and the leads those calls created.
 */
class VoiceCallController extends Controller
{
    public function __construct(private VoiceCallService $voice)
    {
    }

    private function guardAccess(): void
    {
        $u = Auth::guard('crm')->user();
        if (!($u->isAdmin() || $u->isSuperAdmin() || $u->isSalesManager() || $u->isSales())) {
            abort(403, 'Voice calls are limited to admin and sales.');
        }
    }

    public function index(Request $request)
    {
        $this->guardAccess();
        $configured = $this->voice->configured();
        $credits = null;
        $calls = [];
        $error = null;

        if ($configured) {
            try {
                $sub = $this->voice->api()->timeout(15)->get('/user/subscription')->json() ?: [];
                $credits = [
                    'used'  => $sub['character_count'] ?? null,
                    'limit' => $sub['character_limit'] ?? null,
                ];
                $list = $this->voice->api()->timeout(20)->get('/convai/conversations', [
                    'agent_id' => $this->voice->cfg()['agent'], 'page_size' => 25,
                ])->json() ?: [];
                $calls = $list['conversations'] ?? [];
            } catch (\Throwable $e) {
                $error = 'ElevenLabs API se raabta nahi hua: ' . $e->getMessage();
            }
        }

        $leadKeys = collect($calls)->pluck('conversation_id')->map(fn ($i) => 'elvoice-' . $i)->all();
        $callLeads = $leadKeys
            ? CrmEmail::whereIn('external_lead_id', $leadKeys)->get()->keyBy('external_lead_id')
            : collect();

        // Follow-up section: CRM leads that have a phone number (newest first, searchable).
        $q = trim((string) $request->query('q', ''));
        $leads = CrmEmail::whereNotNull('client_phone')->where('client_phone', '!=', '')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('client_name', 'like', "%{$q}%")
                        ->orWhere('client_email', 'like', "%{$q}%")
                        ->orWhere('client_phone', 'like', "%{$q}%")
                        ->orWhere('product_name', 'like', "%{$q}%")
                        ->orWhere('status', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')->limit(60)->get();

        // Latest queue row per lead + overall queue counters.
        $queueRows = VoiceCallQueue::whereIn('crm_email_id', $leads->pluck('id'))
            ->orderBy('id')->get()->keyBy('crm_email_id'); // later rows overwrite earlier = latest wins
        $queueStats = [
            'pending' => VoiceCallQueue::where('status', 'pending')->count(),
            'calling' => VoiceCallQueue::where('status', 'calling')->count(),
        ];

        return view('crm.voice_calls.index', compact(
            'configured', 'credits', 'calls', 'callLeads', 'error', 'leads', 'queueRows', 'queueStats', 'q'
        ));
    }

    /** Manual call to any number typed on the page. */
    public function call(Request $request)
    {
        $this->guardAccess();
        $data = $request->validate(['phone' => 'required|string|max:30']);
        $to = $this->voice->normalizePhone($data['phone']);
        if (!$to) {
            return back()->with('vc_error', 'Number samajh nahi aaya. +923001234567 ya 03001234567 style use karein.');
        }
        set_time_limit(120);
        [$ok, $result] = $this->voice->placeCall($to);
        return $ok
            ? back()->with('vc_success', "Call ja rahi hai {$to} par ({$result}).")
            : back()->with('vc_error', 'Call nahi lagi: ' . $result);
    }

    /** Immediate follow-up call to one lead, with the lead's context given to Sara. */
    public function callLead($id)
    {
        $this->guardAccess();
        $lead = CrmEmail::findOrFail($id);
        $to = $this->voice->normalizePhone($lead->client_phone);
        if (!$to) {
            return back()->with('vc_error', "Lead #{$lead->id} ka phone number ({$lead->client_phone}) E.164 mein nahi badla ja saka.");
        }
        set_time_limit(120);
        [$ok, $result] = $this->voice->placeCall($to, $lead);
        if ($ok) {
            VoiceCallQueue::create([
                'crm_email_id' => $lead->id, 'phone' => $to, 'status' => 'calling',
                'conversation_id' => $result, 'queued_by' => Auth::guard('crm')->id(),
                'note' => 'direct call from page',
            ]);
            return back()->with('vc_success', "Follow-up call ja rahi hai {$lead->client_name} ({$to}) par.");
        }
        return back()->with('vc_error', 'Call nahi lagi: ' . $result);
    }

    /** Queue many leads for one-at-a-time follow-up calls (cron works the queue every minute). */
    public function queueStore(Request $request)
    {
        $this->guardAccess();
        $data = $request->validate(['lead_ids' => 'required|array|min:1', 'lead_ids.*' => 'integer']);
        $leads = CrmEmail::whereIn('id', $data['lead_ids'])
            ->whereNotNull('client_phone')->where('client_phone', '!=', '')->get();

        $already = VoiceCallQueue::whereIn('crm_email_id', $leads->pluck('id'))
            ->whereIn('status', ['pending', 'calling'])->pluck('crm_email_id')->all();

        $added = 0;
        $badPhone = 0;
        foreach ($leads as $lead) {
            if (in_array($lead->id, $already)) {
                continue;
            }
            $to = $this->voice->normalizePhone($lead->client_phone);
            if (!$to) {
                $badPhone++;
                continue;
            }
            VoiceCallQueue::create([
                'crm_email_id' => $lead->id, 'phone' => $to, 'status' => 'pending',
                'queued_by' => Auth::guard('crm')->id(),
            ]);
            $added++;
        }

        $msg = "{$added} leads call queue mein lag gaye — Sara aik aik kar ke sab ko call karegi (har minute agli call).";
        if (count($already)) {
            $msg .= ' ' . count($already) . ' pehle se queue mein thay.';
        }
        if ($badPhone) {
            $msg .= " {$badPhone} ka number theek nahi tha.";
        }
        return back()->with('vc_success', $msg);
    }

    /** Stop the campaign: cancel every pending row (the in-flight call finishes on its own). */
    public function queueCancel()
    {
        $this->guardAccess();
        $n = VoiceCallQueue::where('status', 'pending')->update(['status' => 'cancelled']);
        return back()->with('vc_success', "{$n} pending calls cancel kar diye.");
    }

    public function transcript($id)
    {
        $this->guardAccess();
        if (!preg_match('/^conv_[a-z0-9]+$/i', $id)) {
            abort(404);
        }
        try {
            $conv = $this->voice->api()->timeout(20)->get('/convai/conversations/' . $id)->json() ?: [];
        } catch (\Throwable $e) {
            $conv = ['_error' => $e->getMessage()];
        }
        $lead = CrmEmail::where('external_lead_id', 'elvoice-' . $id)->first();

        return view('crm.voice_calls.transcript', ['conv' => $conv, 'convId' => $id, 'lead' => $lead]);
    }
}
