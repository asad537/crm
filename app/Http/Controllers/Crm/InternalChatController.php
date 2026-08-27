<?php
namespace App\Http\Controllers\Crm;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\CrmUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class InternalChatController extends Controller
{
    public function index()
    {
        $response = $this->getAgents();
        $initialAgents = json_decode($response->getContent(), true) ?: [];

        return view('crm.team_chat.index', compact('initialAgents'));
    }

    public function getAgents()
    {
        $currentUser = Auth::guard('crm')->user();

        $workspaceId = session('crm_workspace_id');
        $query = CrmUser::inWorkspace($workspaceId)->where('id', '!=', $currentUser->id);

        if ($currentUser->isAdmin()) {
            // Admin/super_admin sees everyone
        } elseif ($currentUser->isSalesManager()) {
            // Sales Manager sees Admins (incl. CEO/super_admin), Accounts + Sales Agents
            $query->whereIn('role', ['admin', 'super_admin', 'accounts', 'sales']);
        } else {
            // Sales Agent sees Admins + CEO (super_admin) + Accounts
            $query->whereIn('role', ['admin', 'super_admin', 'accounts']);
        }

        $agents = $query->orderByRaw("role = 'admin' DESC")->get(['id', 'name', 'role', 'last_seen_at']);

        $agentIds = $agents->pluck('id')->all();

        // Batch unread counts: 1 query for all agents (was 1 query per agent — N+1).
        $unreadByAgent = DB::table('crm_internal_messages')
            ->where('workspace_id', $workspaceId)
            ->where('receiver_id', $currentUser->id)
            ->where('is_read', 0)
            ->whereIn('sender_id', $agentIds)
            ->selectRaw('sender_id, COUNT(*) as c')
            ->groupBy('sender_id')
            ->pluck('c', 'sender_id');

        // Batch last-message: latest per agent-pair — 1 query with a subquery instead of N.
        $lastMsgs = DB::table('crm_internal_messages')
            ->where('workspace_id', $workspaceId)
            ->where(function ($q) use ($currentUser, $agentIds) {
                $q->where(function ($x) use ($currentUser, $agentIds) {
                    $x->where('sender_id', $currentUser->id)->whereIn('receiver_id', $agentIds);
                })->orWhere(function ($x) use ($currentUser, $agentIds) {
                    $x->where('receiver_id', $currentUser->id)->whereIn('sender_id', $agentIds);
                });
            })
            ->orderBy('created_at', 'desc')
            ->get(['sender_id', 'receiver_id', 'message_body', 'created_at']);
        // Reduce to latest per counterpart id.
        $lastMsgByAgent = [];
        foreach ($lastMsgs as $m) {
            $other = ((int) $m->sender_id === (int) $currentUser->id) ? (int) $m->receiver_id : (int) $m->sender_id;
            if (!isset($lastMsgByAgent[$other])) $lastMsgByAgent[$other] = $m;
        }

        $mergedAgents = [];
        foreach ($agents as $agent) {
            $agent->unread_count = (int) ($unreadByAgent[$agent->id] ?? 0);
            $lastMsg = $lastMsgByAgent[$agent->id] ?? null;
            $agent->last_message    = $lastMsg ? $lastMsg->message_body : null;
            $agent->last_message_at = $lastMsg ? $lastMsg->created_at   : null;

            // Online status
            if ($agent->last_seen_at) {
                $diffSeconds = now()->diffInSeconds(\Carbon\Carbon::parse($agent->last_seen_at));
                if ($diffSeconds <= 30) {
                    $agent->online_status = 'online';
                } elseif ($diffSeconds <= 120) {
                    $agent->online_status = 'recent';
                } else {
                    $agent->online_status = 'offline';
                }
                $agent->last_seen_human = \Carbon\Carbon::parse($agent->last_seen_at)->diffForHumans();
            } else {
                $agent->online_status = 'offline';
                $agent->last_seen_human = 'Never';
            }

            // Exclude the System Notification bot from showing in the list if it has no messages
            if ($agent->email === 'system@myboxprinting.com' && !$lastMsg && $agent->unread_count == 0) {
                continue;
            }

            $mergedAgents[] = $agent;
        }

        // Fetch Customer Chats
        $customerOrders = DB::table('customer_chats')
            ->select('sales_order_id')
            ->distinct()
            ->get()
            ->pluck('sales_order_id');

        if ($customerOrders->count() > 0) {
            $ordersQuery = \App\SalesOrder::with('lead')->whereIn('id', $customerOrders);
            if (!$currentUser->isAdmin()) {
                $ordersQuery->where('sales_agent_id', $currentUser->id);
            }
            $orders = $ordersQuery->get();
            $orderIds = $orders->pluck('id')->all();

            // Batch unread counts per order (1 query, was N).
            $custUnread = DB::table('customer_chats')
                ->whereIn('sales_order_id', $orderIds)
                ->where('sender_type', 'customer')
                ->where('is_read', 0)
                ->selectRaw('sales_order_id, COUNT(*) as c')
                ->groupBy('sales_order_id')
                ->pluck('c', 'sales_order_id');

            // Batch last-message per order (1 query + reduce, was N).
            $allChats = DB::table('customer_chats')
                ->whereIn('sales_order_id', $orderIds)
                ->orderBy('created_at', 'desc')
                ->get(['sales_order_id', 'message_body', 'created_at']);
            $custLast = [];
            foreach ($allChats as $c) {
                if (!isset($custLast[$c->sales_order_id])) $custLast[$c->sales_order_id] = $c;
            }

            foreach ($orders as $order) {
                $lastMsg = $custLast[$order->id] ?? null;
                $customerName = ($order->lead && $order->lead->client_name) ? $order->lead->client_name : 'Customer';

                $customerObj = new \stdClass();
                $customerObj->id = 'customer-' . $order->id;
                $customerObj->name = $customerName . ' - Order #' . $order->id;
                $customerObj->role = 'customer';
                $customerObj->last_seen_at = null;
                $customerObj->unread_count = (int) ($custUnread[$order->id] ?? 0);
                $customerObj->last_message    = $lastMsg ? $lastMsg->message_body : null;
                $customerObj->last_message_at = $lastMsg ? $lastMsg->created_at   : null;
                $customerObj->online_status = 'offline';
                $customerObj->last_seen_human = 'Never';

                $mergedAgents[] = $customerObj;
            }
        }

        // Sort by last_message_at descending
        usort($mergedAgents, function($a, $b) {
            $timeA = $a->last_message_at ? strtotime($a->last_message_at) : 0;
            $timeB = $b->last_message_at ? strtotime($b->last_message_at) : 0;
            return $timeB <=> $timeA;
        });

        return response()->json($mergedAgents);
    }

    public function ping()
    {
        $user = Auth::guard('crm')->user();
        if ($user) {
            \App\CrmUser::where('id', $user->id)->update(['last_seen_at' => now()]);
        }
        return response()->json(['ok' => true]);
    }

    public function getUnreadTotal()
    {
        $currentUser = Auth::guard('crm')->user();
        $workspaceId = session('crm_workspace_id');
        $total = DB::table('crm_internal_messages')
            ->where('workspace_id', $workspaceId)
            ->where('receiver_id', $currentUser->id)
            ->where('is_read', 0)
            ->count();

        $customerOrders = DB::table('customer_chats')
            ->select('sales_order_id')
            ->distinct()
            ->get()
            ->pluck('sales_order_id');

        $ordersQuery = \App\SalesOrder::whereIn('id', $customerOrders);
        if (!$currentUser->isAdmin()) {
            $ordersQuery->where('sales_agent_id', $currentUser->id);
        }
        $validOrderIds = $ordersQuery->pluck('id');

        $customerUnread = DB::table('customer_chats')
            ->whereIn('sales_order_id', $validOrderIds)
            ->where('sender_type', 'customer')
            ->where('is_read', 0)
            ->count();

        return response()->json(['total' => $total + $customerUnread]);
    }

    public function getMessages(Request $request, $agentId)
    {
        $currentUser = Auth::guard('crm')->user();
        $workspaceId = session('crm_workspace_id');

        if (strpos($agentId, 'customer-') === 0) {
            $orderId = str_replace('customer-', '', $agentId);
            $allowedOrder = \App\SalesOrder::whereKey($orderId)->exists();
            abort_unless($allowedOrder, 404);
            $chats = DB::table('customer_chats')
                ->where('sales_order_id', $orderId)
                ->orderBy('created_at', 'asc')
                ->get();

            $messages = [];
            foreach ($chats as $chat) {
                $msg = new \stdClass();
                $msg->id = $chat->id;
                $msg->sender_id = $chat->sender_type === 'customer' ? $agentId : $chat->crm_user_id;
                $msg->receiver_id = $chat->sender_type === 'customer' ? $currentUser->id : $agentId;
                $msg->message_body = $chat->message_body;
                $msg->attachment_path = null;
                $msg->attachment_name = null;
                $msg->is_forwarded = 0;
                $msg->is_read = $chat->is_read;
                $msg->created_at = $chat->created_at;
                $msg->updated_at = $chat->updated_at;
                $messages[] = $msg;
            }

            // Mark as read
            DB::table('customer_chats')
                ->where('sales_order_id', $orderId)
                ->where('sender_type', 'customer')
                ->update(['is_read' => 1]);

            return response()->json($messages);
        }

        abort_unless(CrmUser::inWorkspace($workspaceId)->whereKey($agentId)->exists(), 404);

        $messages = DB::table('crm_internal_messages')
            ->where('workspace_id', $workspaceId)
            ->where(function ($conversation) use ($currentUser, $agentId) {
                $conversation->where(function ($q) use ($currentUser, $agentId) {
                    $q->where('sender_id', $currentUser->id)->where('receiver_id', $agentId);
                })->orWhere(function ($q) use ($currentUser, $agentId) {
                    $q->where('sender_id', $agentId)->where('receiver_id', $currentUser->id);
                });
            })
            ->orderBy('created_at', 'asc')
            ->get();

        // Mark as read
        DB::table('crm_internal_messages')
            ->where('workspace_id', $workspaceId)
            ->where('sender_id', $agentId)
            ->where('receiver_id', $currentUser->id)
            ->update(['is_read' => 1]);

        return response()->json($messages);
    }

    public function sendMessage(Request $request)
    {
        $receiverId = $request->receiver_id;
        $isCustomer = strpos($receiverId, 'customer-') === 0;

        $rules = [
            'receiver_id' => 'required',
            'message_body' => 'nullable|string',
            'attachment' => 'nullable|file|max:10240', // Max 10MB
        ];

        if (!$isCustomer) {
            $rules['receiver_id'] = 'required|exists:crm_users,id';
        }

        $request->validate($rules);

        $currentUser = Auth::guard('crm')->user();
        $workspaceId = session('crm_workspace_id');
        $attachmentPath = null;
        $attachmentName = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentName = $file->getClientOriginalName();
            // Store directly in public folder to avoid symlink issues on server
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move('chat_attachments', $fileName);
            $attachmentPath = 'chat_attachments/' . $fileName;
        }

        if ($isCustomer) {
            $orderId = str_replace('customer-', '', $receiverId);
            abort_unless(\App\SalesOrder::whereKey($orderId)->exists(), 404);
            
            $body = $request->message_body ?? '';
            if ($attachmentPath) {
                $url = url($attachmentPath);
                $body .= "\n\n📎 Attachment: <a href='{$url}' target='_blank'>{$attachmentName}</a>";
            }

            $id = DB::table('customer_chats')->insertGetId([
                'sales_order_id' => $orderId,
                'crm_user_id'    => $currentUser->id,
                'sender_type'    => 'agent',
                'message_body'   => $body,
                'is_read'        => 0,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            $message = new \stdClass();
            $message->id = $id;
            $message->sender_id = $currentUser->id;
            $message->receiver_id = $receiverId;
            $message->message_body = $body;
            $message->attachment_path = null;
            $message->attachment_name = null;
            $message->is_forwarded = 0;
            $message->is_read = 0;
            $message->created_at = now()->toDateTimeString();
            $message->updated_at = now()->toDateTimeString();

            return response()->json([
                'success' => true,
                'data' => $message
            ]);
        }

        abort_unless(CrmUser::inWorkspace($workspaceId)->whereKey($receiverId)->exists(), 404);

        // If this conversation is already tied to an inquiry, keep the reply tied to it too,
        // and mirror it back into that inquiry's "Message to Agent" thread.
        $linkedEmailId = null;
        if (\Illuminate\Support\Facades\Schema::hasColumn('crm_internal_messages', 'crm_email_id')) {
            $prior = DB::table('crm_internal_messages')
                ->where('workspace_id', $workspaceId)
                ->whereNotNull('crm_email_id')
                ->where(function ($q) use ($currentUser, $receiverId) {
                    $q->where(function ($x) use ($currentUser, $receiverId) {
                        $x->where('sender_id', $currentUser->id)->where('receiver_id', $receiverId);
                    })->orWhere(function ($x) use ($currentUser, $receiverId) {
                        $x->where('sender_id', $receiverId)->where('receiver_id', $currentUser->id);
                    });
                })
                ->orderBy('id', 'desc')
                ->first();
            $linkedEmailId = $prior->crm_email_id ?? null;
        }

        $insert = [
            'workspace_id' => $workspaceId,
            'sender_id' => $currentUser->id,
            'receiver_id' => $receiverId,
            'message_body' => $request->message_body ?? '',
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'is_forwarded' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if ($linkedEmailId) {
            $insert['crm_email_id'] = $linkedEmailId;
        }
        $id = DB::table('crm_internal_messages')->insertGetId($insert);

        // Mirror the reply onto the inquiry thread so admins see agent replies there.
        if ($linkedEmailId && trim((string) ($request->message_body ?? '')) !== '') {
            try {
                \App\CrmInquiryNote::create([
                    'crm_email_id' => $linkedEmailId,
                    'sender_id'    => $currentUser->id,
                    'sender_name'  => $currentUser->name,
                    'sender_role'  => method_exists($currentUser, 'getRoleLabel') ? $currentUser->getRoleLabel() : $currentUser->role,
                    'body'         => $request->message_body,
                ]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Team chat -> inquiry note mirror failed: ' . $e->getMessage());
            }
        }

        $message = DB::table('crm_internal_messages')->where('workspace_id', $workspaceId)->where('id', $id)->first();

        return response()->json([
            'success' => true,
            'data' => $message
        ]);
    }

    public function forwardMessage(Request $request)
    {
        $request->validate([
            'message_id' => 'required',
            'receiver_ids' => 'required|array'
        ]);

        $workspaceId = session('crm_workspace_id');
        $original = DB::table('crm_internal_messages')->where('workspace_id', $workspaceId)->where('id', $request->message_id)->first();
        if (!$original)
            return response()->json(['success' => false, 'message' => 'Message not found']);

        $allowedReceiverIds = CrmUser::inWorkspace($workspaceId)->whereIn('id', $request->receiver_ids)->pluck('id')->map(function ($id) { return (string) $id; })->all();
        foreach ($request->receiver_ids as $rid) {
            if (!in_array((string) $rid, $allowedReceiverIds, true)) continue;
            DB::table('crm_internal_messages')->insert([
                'workspace_id' => $workspaceId,
                'sender_id' => Auth::guard('crm')->id(),
                'receiver_id' => $rid,
                'message_body' => $original->message_body,
                'attachment_path' => $original->attachment_path,
                'attachment_name' => $original->attachment_name,
                'is_forwarded' => 1,
                'is_read' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function deleteMessage($id)
    {
        $workspaceId = session('crm_workspace_id');
        $message = DB::table('crm_internal_messages')->where('workspace_id', $workspaceId)->where('id', $id)->first();
        if (!$message)
            return response()->json(['success' => false, 'message' => 'Message not found']);

        // Only sender can delete
        if ($message->sender_id != Auth::guard('crm')->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized']);
        }

        DB::table('crm_internal_messages')->where('workspace_id', $workspaceId)->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }

    public function editMessage(Request $request, $id)
    {
        $request->validate(['message_body' => 'required']);

        $workspaceId = session('crm_workspace_id');
        $message = DB::table('crm_internal_messages')->where('workspace_id', $workspaceId)->where('id', $id)->first();
        if (!$message)
            return response()->json(['success' => false, 'message' => 'Message not found']);

        if ($message->sender_id != Auth::guard('crm')->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized']);
        }

        DB::table('crm_internal_messages')->where('workspace_id', $workspaceId)->where('id', $id)->update([
            'message_body' => $request->message_body,
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }
}
