<?php

namespace App\Http\Controllers\Crm;

use App\CrmEmail;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

/**
 * Sara (ElevenLabs) voice calls from inside the CRM: place outbound calls,
 * see recent calls + transcripts, and the leads they created.
 * Credentials live in .env (ELEVENLABS_API_KEY / _AGENT_ID / _PHONE_ID) and are
 * kept in sync by /opt/elvoice/el_migrate.py when the ElevenLabs account changes.
 */
class VoiceCallController extends Controller
{
    private function guardAccess(): void
    {
        $u = Auth::guard('crm')->user();
        if (!($u->isAdmin() || $u->isSuperAdmin() || $u->isSalesManager() || $u->isSales())) {
            abort(403, 'Voice calls are limited to admin and sales.');
        }
    }

    private function cfg(): array
    {
        return [
            'key'   => (string) env('ELEVENLABS_API_KEY', ''),
            'agent' => (string) env('ELEVENLABS_AGENT_ID', ''),
            'phone' => (string) env('ELEVENLABS_PHONE_ID', ''),
        ];
    }

    private function api()
    {
        return Http::withHeaders(['xi-api-key' => $this->cfg()['key']])
            ->baseUrl('https://api.elevenlabs.io/v1');
    }

    public function index()
    {
        $this->guardAccess();
        $cfg = $this->cfg();
        $configured = $cfg['key'] && $cfg['agent'] && $cfg['phone'];
        $credits = null;
        $calls = [];
        $error = null;

        if ($configured) {
            try {
                $sub = $this->api()->timeout(15)->get('/user/subscription')->json() ?: [];
                $credits = [
                    'used'  => $sub['character_count'] ?? null,
                    'limit' => $sub['character_limit'] ?? null,
                ];
                $list = $this->api()->timeout(20)->get('/convai/conversations', [
                    'agent_id' => $cfg['agent'], 'page_size' => 25,
                ])->json() ?: [];
                $calls = $list['conversations'] ?? [];
            } catch (\Throwable $e) {
                $error = 'ElevenLabs API se raabta nahi hua: ' . $e->getMessage();
            }
        }

        $leadKeys = collect($calls)->pluck('conversation_id')
            ->map(fn ($i) => 'elvoice-' . $i)->all();
        $leads = $leadKeys
            ? CrmEmail::whereIn('external_lead_id', $leadKeys)->get()->keyBy('external_lead_id')
            : collect();

        return view('crm.voice_calls.index', compact('configured', 'credits', 'calls', 'leads', 'error'));
    }

    public function call(Request $request)
    {
        $this->guardAccess();
        $data = $request->validate(['phone' => 'required|string|max:30']);

        $raw = preg_replace('/[^0-9+]/', '', $data['phone']);
        if (str_starts_with($raw, '+')) {
            $to = '+' . preg_replace('/\D/', '', substr($raw, 1));
        } elseif (str_starts_with($raw, '00')) {
            $to = '+' . substr($raw, 2);
        } elseif (str_starts_with($raw, '0') && strlen($raw) === 11) {
            $to = '+92' . substr($raw, 1); // Pakistani local format, e.g. 03454428186
        } else {
            $to = '+' . $raw;
        }
        if (!preg_match('/^\+[1-9]\d{9,14}$/', $to)) {
            return back()->with('vc_error', "Number samajh nahi aaya ({$to}). +923001234567 ya 03001234567 style use karein.");
        }

        $cfg = $this->cfg();
        if (!($cfg['key'] && $cfg['agent'] && $cfg['phone'])) {
            return back()->with('vc_error', 'ElevenLabs keys CRM .env mein set nahi hain.');
        }

        set_time_limit(120);
        try {
            $res = $this->api()->timeout(90)->post('/convai/sip-trunk/outbound-call', [
                'agent_id' => $cfg['agent'],
                'agent_phone_number_id' => $cfg['phone'],
                'to_number' => $to,
            ]);
            $body = $res->json() ?: [];
            if ($res->ok() && ($body['success'] ?? false)) {
                return back()->with('vc_success', "Call ja rahi hai {$to} par (" . ($body['conversation_id'] ?? '') . '). Baat khatam hone ke ~1 minute baad transcript/lead yahan aa jayegi.');
            }
            $msg = $body['message'] ?? ('HTTP ' . $res->status());
            if (str_contains($msg, 'not available')) {
                $msg .= ' — aam taur par iska matlab ElevenLabs ke credits khatam hain.';
            }
            return back()->with('vc_error', 'Call nahi lagi: ' . $msg);
        } catch (\Throwable $e) {
            return back()->with('vc_error', 'Request time out — lambi ring par call phir bhi lag sakti hai; neeche list refresh kar ke dekhein. (' . $e->getMessage() . ')');
        }
    }

    public function transcript($id)
    {
        $this->guardAccess();
        if (!preg_match('/^conv_[a-z0-9]+$/i', $id)) {
            abort(404);
        }
        try {
            $conv = $this->api()->timeout(20)->get('/convai/conversations/' . $id)->json() ?: [];
        } catch (\Throwable $e) {
            $conv = ['_error' => $e->getMessage()];
        }
        $lead = CrmEmail::where('external_lead_id', 'elvoice-' . $id)->first();

        return view('crm.voice_calls.transcript', ['conv' => $conv, 'convId' => $id, 'lead' => $lead]);
    }
}
