<?php

namespace App\Http\Controllers\Crm;

use App\CrmMailAccount;
use App\CrmMailAttachment;
use App\CrmMailFolder;
use App\CrmMailMessage;
use App\Http\Controllers\Controller;
use App\Services\Mail\ImapSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

/**
 * Read side of the mail client: folders with counts, message lists (per folder type /
 * account, searchable), a single message with sanitised HTML + attachments, read/star
 * state, and authorised attachment downloads. Every query is restricted to mailboxes
 * the caller may view (own, or shared with admins — CrmMailAccountPolicy).
 */
class MailboxController extends Controller
{
    public const TYPES = ['inbox', 'starred', 'sent', 'drafts', 'archive', 'junk', 'trash'];

    private function user()
    {
        return Auth::guard('crm')->user();
    }

    /** IDs of the accounts the current user may read; optionally narrowed to one (403 if not allowed). */
    private function visibleAccountIds(?int $only = null): array
    {
        $user = $this->user();
        abort_unless($user, 403);
        $q = CrmMailAccount::withoutGlobalScopes()->whereNull('deleted_at');
        if ($user->isAdmin() || $user->isSalesManager()) {
            $q->where(fn ($w) => $w->where('crm_user_id', $user->id)->orWhere('share_with_admin', true));
        } else {
            $q->where('crm_user_id', $user->id);
        }
        if ($user->workspaces()->count() && !$user->isSuperAdmin()) {
            // keep to the active workspace when the account carries one
            $ws = \App\Support\CrmWorkspaceContext::id();
            if ($ws) $q->where(fn ($w) => $w->where('workspace_id', $ws)->orWhereNull('workspace_id'));
        }
        $ids = $q->pluck('id')->map(fn ($i) => (int) $i)->all();
        if ($only !== null) {
            abort_unless(in_array($only, $ids, true), 403, 'You do not have access to this mailbox.');
            return [$only];
        }
        return $ids;
    }

    private function messageFor(int $id, string $ability = 'view'): CrmMailMessage
    {
        $message = CrmMailMessage::withoutGlobalScopes()->with('account')->findOrFail($id);
        $account = $message->account()->withoutGlobalScopes()->firstOrFail();
        Gate::forUser($this->user())->authorize($ability, $account);
        return $message;
    }

    // ---- folders --------------------------------------------------------

    public function folders(Request $request)
    {
        $ids = $this->visibleAccountIds($request->filled('account') ? (int) $request->input('account') : null);
        $counts = [];
        foreach (self::TYPES as $t) $counts[$t] = ['total' => 0, 'unread' => 0];
        if ($ids) {
            $rows = CrmMailFolder::withoutGlobalScopes()->whereIn('account_id', $ids)
                ->select('type', DB::raw('SUM(message_count) total'), DB::raw('SUM(unread_count) unread'))
                ->groupBy('type')->get();
            foreach ($rows as $r) {
                if (isset($counts[$r->type])) {
                    $counts[$r->type] = ['total' => (int) $r->total, 'unread' => (int) $r->unread];
                }
            }
            $starred = CrmMailMessage::withoutGlobalScopes()->whereIn('account_id', $ids)->where('is_starred', true);
            $counts['starred'] = ['total' => (clone $starred)->count(), 'unread' => (clone $starred)->where('is_read', false)->count()];
            // real unread for inbox should ignore outgoing copies
            $counts['inbox']['unread'] = CrmMailMessage::withoutGlobalScopes()->whereIn('account_id', $ids)
                ->whereHas('folder', fn ($f) => $f->withoutGlobalScopes()->where('type', 'inbox'))
                ->where('is_read', false)->where('is_outgoing', false)->count();
        }
        $custom = $ids ? CrmMailFolder::withoutGlobalScopes()->whereIn('account_id', $ids)->where('type', 'custom')
            ->orderBy('name')->get(['id', 'account_id', 'name', 'path', 'message_count', 'unread_count']) : collect();

        return response()->json(['counts' => $counts, 'custom' => $custom]);
    }

    // ---- list -----------------------------------------------------------

    public function messages(Request $request)
    {
        $request->validate([
            'account' => 'nullable|integer',
            'type' => 'nullable|in:' . implode(',', self::TYPES),
            'folder' => 'nullable|integer',
            'q' => 'nullable|string|max:200',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:10|max:100',
            'unread' => 'nullable|boolean',
        ]);
        $ids = $this->visibleAccountIds($request->filled('account') ? (int) $request->input('account') : null);
        if (!$ids) {
            return response()->json(['messages' => [], 'page' => 1, 'has_more' => false]);
        }

        $type = $request->input('type', 'inbox');
        $q = CrmMailMessage::withoutGlobalScopes()->whereIn('crm_mail_messages.account_id', $ids)
            ->join('crm_mail_folders as f', 'f.id', '=', 'crm_mail_messages.folder_id')
            ->select('crm_mail_messages.*', 'f.type as folder_type', 'f.name as folder_name');

        if ($request->filled('folder')) {
            $q->where('crm_mail_messages.folder_id', (int) $request->input('folder'));
        } elseif ($type === 'starred') {
            $q->where('crm_mail_messages.is_starred', true);
        } else {
            $q->where('f.type', $type);
        }
        if ($request->boolean('unread')) {
            $q->where('crm_mail_messages.is_read', false);
        }
        if ($term = trim((string) $request->input('q'))) {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';
            $q->where(function ($w) use ($term, $like) {
                $w->whereRaw('MATCH(crm_mail_messages.subject, crm_mail_messages.text_body) AGAINST (? IN NATURAL LANGUAGE MODE)', [$term])
                  ->orWhere('crm_mail_messages.subject', 'like', $like)
                  ->orWhere('crm_mail_messages.from_email', 'like', $like)
                  ->orWhere('crm_mail_messages.from_name', 'like', $like);
            });
        }

        $perPage = (int) $request->input('per_page', 50);
        $page = (int) $request->input('page', 1);
        $rows = $q->orderByDesc('crm_mail_messages.received_at')->orderByDesc('crm_mail_messages.id')
            ->skip(($page - 1) * $perPage)->take($perPage + 1)->get();
        $hasMore = $rows->count() > $perPage;
        $rows = $rows->take($perPage);

        $out = $rows->map(fn ($m) => [
            'id' => $m->id,
            'account_id' => $m->account_id,
            'thread_id' => $m->thread_id,
            'folder_type' => $m->folder_type,
            'folder_name' => $m->folder_name,
            'subject' => $m->subject,
            'from_name' => $m->from_name,
            'from_email' => $m->from_email,
            'to' => $m->to_json,
            'snippet' => $m->snippet,
            'is_read' => (bool) $m->is_read,
            'is_starred' => (bool) $m->is_starred,
            'is_outgoing' => (bool) $m->is_outgoing,
            'has_attachments' => (bool) $m->has_attachments,
            'crm_email_id' => $m->crm_email_id,
            'received_at' => optional($m->received_at)->toIso8601String(),
        ])->values();

        return response()->json(['messages' => $out, 'page' => $page, 'has_more' => $hasMore]);
    }

    // ---- single message -------------------------------------------------

    public function show($id, ImapSyncService $sync)
    {
        $message = $this->messageFor((int) $id);
        $attachments = CrmMailAttachment::where('message_id', $message->id)->get();

        $html = $message->html_body;
        if ($html) {
            // inline images: cid:xyz → authorised attachment URL
            foreach ($attachments as $a) {
                if ($a->content_id) {
                    $html = str_ireplace(['cid:' . $a->content_id, 'cid:<' . $a->content_id . '>'], route('crm.mail.attachments.show', $a->id), $html);
                }
            }
        }

        if (!$message->is_read) {
            $message->forceFill(['is_read' => true])->saveQuietly();
            if ($folder = $message->folder()->withoutGlobalScopes()->first()) $sync->refreshFolderCounts($folder);
            if ($thread = $message->thread()->withoutGlobalScopes()->first()) app(\App\Services\Mail\MailThreader::class)->refresh($thread);
        }

        $thread = $message->thread_id
            ? CrmMailMessage::withoutGlobalScopes()->where('thread_id', $message->thread_id)->where('id', '!=', $message->id)
                ->orderBy('received_at')->get(['id', 'subject', 'from_name', 'from_email', 'snippet', 'is_outgoing', 'is_read', 'received_at', 'has_attachments'])
            : collect();

        return response()->json([
            'message' => [
                'id' => $message->id,
                'account_id' => $message->account_id,
                'account_email' => optional($message->account)->email_address,
                'thread_id' => $message->thread_id,
                'crm_email_id' => $message->crm_email_id,
                'subject' => $message->subject,
                'from_name' => $message->from_name,
                'from_email' => $message->from_email,
                'to' => $message->to_json ?: [],
                'cc' => $message->cc_json ?: [],
                'reply_to' => $message->reply_to,
                'html' => $html,
                'text' => $message->text_body,
                'is_read' => true,
                'is_starred' => (bool) $message->is_starred,
                'is_outgoing' => (bool) $message->is_outgoing,
                'received_at' => optional($message->received_at)->toIso8601String(),
                'message_id' => $message->message_id,
            ],
            'attachments' => $attachments->where('is_inline', false)->values()->map(fn ($a) => [
                'id' => $a->id, 'name' => $a->original_name, 'mime' => $a->mime_type, 'size' => (int) $a->size,
                'url' => route('crm.mail.attachments.show', $a->id),
            ]),
            'thread' => $thread->map(fn ($m) => [
                'id' => $m->id, 'subject' => $m->subject, 'from_name' => $m->from_name, 'from_email' => $m->from_email,
                'snippet' => $m->snippet, 'is_outgoing' => (bool) $m->is_outgoing, 'is_read' => (bool) $m->is_read,
                'has_attachments' => (bool) $m->has_attachments, 'received_at' => optional($m->received_at)->toIso8601String(),
            ])->values(),
        ]);
    }

    // ---- state ----------------------------------------------------------

    public function star($id)
    {
        $message = $this->messageFor((int) $id, 'update');
        $message->forceFill(['is_starred' => !$message->is_starred])->saveQuietly();
        return response()->json(['id' => $message->id, 'is_starred' => (bool) $message->is_starred]);
    }

    public function read(Request $request, $id, ImapSyncService $sync)
    {
        $request->validate(['read' => 'required|boolean']);
        $message = $this->messageFor((int) $id, 'update');
        $message->forceFill(['is_read' => $request->boolean('read')])->saveQuietly();
        if ($folder = $message->folder()->withoutGlobalScopes()->first()) $sync->refreshFolderCounts($folder);
        if ($thread = $message->thread()->withoutGlobalScopes()->first()) app(\App\Services\Mail\MailThreader::class)->refresh($thread);
        return response()->json(['id' => $message->id, 'is_read' => (bool) $message->is_read]);
    }

    /** Trigger a sync for one visible account (owner only) — returns stats. */
    public function sync(Request $request, ImapSyncService $sync)
    {
        $account = CrmMailAccount::withoutGlobalScopes()->findOrFail((int) $request->input('account'));
        Gate::forUser($this->user())->authorize('update', $account);
        $stats = $sync->syncAccount($account, ['cap' => 100]);
        return response()->json($stats);
    }

    // ---- attachments ----------------------------------------------------

    public function attachment($id)
    {
        $att = CrmMailAttachment::findOrFail((int) $id);
        $this->messageFor((int) $att->message_id); // authorises via the owning mailbox
        $disk = Storage::disk($att->disk ?: 'local');
        abort_unless($disk->exists($att->path), 404);
        $mime = $att->mime_type ?: 'application/octet-stream';
        $inline = str_starts_with($mime, 'image/') || $mime === 'application/pdf';
        return $disk->response($att->path, $att->original_name, [
            'Content-Type' => $inline ? $mime : 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => ($inline ? 'inline' : 'attachment') . '; filename="' . addslashes($att->original_name) . '"',
        ]);
    }
}
