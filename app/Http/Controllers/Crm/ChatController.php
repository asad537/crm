<?php

namespace App\Http\Controllers\Crm;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\CrmEmail;
use App\CrmMessage;
use Illuminate\Support\Facades\Artisan;

class ChatController extends Controller
{
    public function index()
    {
        return view('crm.chats.index');
    }

    public function chatList()
    {
        $user = \Auth::guard('crm')->user();
        $query = CrmEmail::whereHas('messages')->select([
            'id',
            'client_name',
            'client_email',
            'product_name',
            'subject',
            'assigned_to',
        ]);

        if ($user && !$user->isAdmin() && !$user->isSalesManager()) {
            $query->where('assigned_to', $user->id);
        }

        // Get emails that have at least one message
        $chats = $query->with(['latestMessage' => function ($q) {
                $q->select('id', 'crm_email_id', 'created_at');
            }])
            ->withCount(['messages as unread_count' => function($q) {
                $q->where('is_read', false)->where('sender_type', 'client');
            }])
            ->get()
            ->sortByDesc(function($email) {
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
