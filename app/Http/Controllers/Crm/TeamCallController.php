<?php

namespace App\Http\Controllers\Crm;

use App\CrmTeamCall;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Team Chat voice / video calls. The media goes browser to browser over WebRTC; this only passes the offer and
 * answer between the two browsers, keeps the call's status, and writes the call's log into the conversation.
 * There is no websocket server, so both browsers poll (see crm.team_chat._call_widget).
 */
class TeamCallController extends Controller
{
    /** A call nobody answers stops ringing after this long. */
    const RING_SECONDS = 45;
    /** A live call is closed once one side has not been heard from for this long (closed tab, lost network). */
    const LOST_SECONDS = 30;

    public function start(Request $request)
    {
        $data = $request->validate([
            'receiver_id' => 'required|integer',
            'type' => 'required|in:audio,video',
            'offer' => 'required|string|max:200000',
        ]);
        $user = Auth::guard('crm')->user();
        $workspaceId = session('crm_workspace_id');
        $teammate = InternalChatController::teammates($user, $workspaceId)->whereKey($data['receiver_id'])->first();
        abort_unless($teammate, 403, 'You cannot call this teammate.');

        $this->closeStale();
        $inCall = fn ($id) => CrmTeamCall::live()->where(fn ($q) => $q->where('caller_id', $id)->orWhere('callee_id', $id))->exists();
        if ($inCall($user->id)) {
            return response()->json(['success' => false, 'message' => 'You are already on a call.'], 409);
        }
        if ($inCall($teammate->id)) {
            return response()->json(['success' => false, 'message' => $teammate->name . ' is on another call.'], 409);
        }

        $call = CrmTeamCall::create([
            'workspace_id' => $workspaceId,
            'caller_id' => $user->id,
            'callee_id' => $teammate->id,
            'type' => $data['type'],
            'status' => 'ringing',
            'offer' => $data['offer'],
            'caller_seen_at' => now(),
        ]);

        return response()->json(['success' => true, 'id' => $call->id, 'ring_seconds' => self::RING_SECONDS]);
    }

    /** Polled on every CRM page: is somebody calling me right now? */
    public function incoming()
    {
        $this->closeStale();
        $call = CrmTeamCall::with('caller:id,name')
            ->where('workspace_id', session('crm_workspace_id'))
            ->where('callee_id', Auth::guard('crm')->id())
            ->where('status', 'ringing')
            ->latest('id')
            ->first();

        return response()->json(['call' => $call ? [
            'id' => $call->id,
            'type' => $call->type,
            'offer' => $call->offer,
            'caller_id' => $call->caller_id,
            'caller_name' => optional($call->caller)->name ?: 'Teammate',
        ] : null]);
    }

    /** Polled by both sides during a call. */
    public function show($id)
    {
        $call = $this->callFor($id);
        $userId = Auth::guard('crm')->id();
        $call->forceFill([(int) $call->caller_id === (int) $userId ? 'caller_seen_at' : 'callee_seen_at' => now()])->save();
        $this->closeStale();
        $call->refresh();

        return response()->json([
            'id' => $call->id,
            'status' => $call->status,
            // Only the caller needs the answer.
            'answer' => (int) $call->caller_id === (int) $userId ? $call->answer : null,
        ]);
    }

    public function answer(Request $request, $id)
    {
        $data = $request->validate(['answer' => 'required|string|max:200000']);
        $call = $this->callFor($id);
        abort_unless((int) $call->callee_id === (int) Auth::guard('crm')->id(), 403);

        $updated = CrmTeamCall::whereKey($call->id)->where('status', 'ringing')->update([
            'status' => 'active',
            'answer' => $data['answer'],
            'answered_at' => now(),
            'caller_seen_at' => now(),
            'callee_seen_at' => now(),
            'updated_at' => now(),
        ]);
        if (!$updated) {
            return response()->json(['success' => false, 'message' => 'This call has already ended.'], 409);
        }

        return response()->json(['success' => true]);
    }

    public function decline($id)
    {
        $call = $this->callFor($id);
        abort_unless((int) $call->callee_id === (int) Auth::guard('crm')->id(), 403);
        $this->finish($call, 'declined', Auth::guard('crm')->id());

        return response()->json(['success' => true]);
    }

    /** Hang up: a call that was never answered counts as missed for the other person. */
    public function end($id)
    {
        $call = $this->callFor($id);
        $this->finish($call, $call->status === 'active' ? 'ended' : 'missed', Auth::guard('crm')->id());

        return response()->json(['success' => true]);
    }

    protected function callFor($id): CrmTeamCall
    {
        $call = CrmTeamCall::where('workspace_id', session('crm_workspace_id'))->findOrFail($id);
        abort_unless($call->isParticipant(Auth::guard('crm')->id()), 403);

        return $call;
    }

    /** Close calls nobody can still be on. Runs on the polls, as there is no background worker to rely on. */
    protected function closeStale(): void
    {
        $unanswered = CrmTeamCall::where('status', 'ringing')->where('created_at', '<', now()->subSeconds(self::RING_SECONDS))->get();
        foreach ($unanswered as $call) {
            $this->finish($call, 'missed', null);
        }
        $lost = CrmTeamCall::where('status', 'active')
            ->where(fn ($q) => $q->where('caller_seen_at', '<', now()->subSeconds(self::LOST_SECONDS))
                ->orWhere('callee_seen_at', '<', now()->subSeconds(self::LOST_SECONDS)))
            ->get();
        foreach ($lost as $call) {
            $this->finish($call, 'ended', null);
        }
    }

    protected function finish(CrmTeamCall $call, string $status, $endedBy): void
    {
        // Both sides can end at once: only the update that actually closes the call writes the log.
        $closed = CrmTeamCall::whereKey($call->id)->live()->update([
            'status' => $status,
            'ended_at' => now(),
            'ended_by' => $endedBy,
            'updated_at' => now(),
        ]);
        if (!$closed) {
            return;
        }

        $kind = $call->type === 'video' ? 'video' : 'voice';
        if ($status === 'ended') {
            $seconds = $call->answered_at ? max(0, (int) $call->answered_at->diffInSeconds(now())) : 0;
            $body = ucfirst($kind) . ' call · ' . sprintf('%d:%02d', floor($seconds / 60), $seconds % 60);
        } else {
            $body = ucfirst($status) . ' ' . $kind . ' call';
        }

        DB::table('crm_internal_messages')->insert([
            'workspace_id' => $call->workspace_id,
            'sender_id' => $call->caller_id,
            'receiver_id' => $call->callee_id,
            'message_body' => $body,
            'call_id' => $call->id,
            'is_forwarded' => 0,
            // A missed call stays unread for the person who missed it.
            'is_read' => $status === 'missed' ? 0 : 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
