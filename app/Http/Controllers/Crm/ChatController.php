<?php

namespace App\Http\Controllers\Crm;

use App\CrmEmail;
use App\CrmMailAccount;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;

/**
 * Mail (Outlook-style client built on the former Live Chat page).
 * Until per-account IMAP sync lands, "conversations" are CRM leads that have
 * messages; they can be filtered by the mailbox they are linked to
 * (crm_emails.mail_account_id).
 */
class ChatController extends Controller
{
    public function index()
    {
        return view('crm.chats.index');
    }

    public function chatList(Request $request)
    {
        $user = \Auth::guard('crm')->user();
        $query = CrmEmail::whereHas('messages')->select([
            'id',
            'client_name',
            'client_email',
            'product_name',
            'subject',
            'assigned_to',
            'mail_account_id',
            'status',
        ]);

        if ($user && !$user->isAdmin() && !$user->isSalesManager()) {
            $query->where('assigned_to', $user->id);
        }

        // Optional mailbox filter — only mailboxes the caller may view (owner, or shared with admins).
        if ($request->filled('account')) {
            $account = CrmMailAccount::find((int) $request->input('account'));
            if (!$account || !Gate::forUser($user)->allows('view', $account)) {
                abort(403, 'You do not have access to this mailbox.');
            }
            $query->where('mail_account_id', $account->id);
        }

        $chats = $query->with(['latestMessage' => function ($q) {
                $q->select('id', 'crm_email_id', 'created_at', 'sender_type', 'message_body');
            }])
            ->withCount(['messages as unread_count' => function ($q) {
                $q->where('is_read', false)->where('sender_type', 'client');
            }])
            ->get()
            ->map(function ($email) {
                if ($email->latestMessage) {
                    // Keep the payload small: a plain-text preview is enough for the list.
                    $email->latestMessage->message_body = \Illuminate\Support\Str::limit(
                        trim(preg_replace('/\s+/', ' ', strip_tags((string) $email->latestMessage->message_body))), 140
                    );
                }
                return $email;
            })
            ->sortByDesc(function ($email) {
                $message = $email->latestMessage;
                return $message ? $message->created_at->timestamp : 0;
            })
            ->values();

        return response()->json($chats);
    }

    public function syncInbox()
    {
        $user = \Auth::guard('crm')->user();
        if (!$user || !$user->email_user || !$user->email_pass) {
            return response()->json(['success' => true, 'synced' => false]);
        }

        $lockPath = storage_path('framework/cache/imap-sync-' . $user->id . '.lock');
        $lock = @fopen($lockPath, 'c');
        if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
            if ($lock) fclose($lock);
            return response()->json(['success' => true, 'synced' => false, 'busy' => true]);
        }

        try {
            Artisan::call('crm:fetch-emails', [
                '--user' => $user->id,
                '--mark-read' => true,
            ]);
            return response()->json(['success' => true, 'synced' => true]);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
