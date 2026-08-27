<?php

namespace App\Http\Controllers;

use App\SalesOrder;
use App\CrmEmail;
use App\CrmUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class CustomerPortalController extends Controller
{
    // Customer Portal Login Page
    public function loginPage(Request $request)
    {
        if (session('portal_order_id')) {
            $order = SalesOrder::find(session('portal_order_id'));
            if ($order) return redirect()->route('portal.track', $order->id)->with('_token_query', $this->getToken($order));
        }
        $portalBrand = [
            'name' => 'Secure Customer Portal',
            'primary' => '#2563eb',
            'primary_dark' => '#164fbb',
            'primary_soft' => '#eff6ff',
            'primary_ring' => 'rgba(37, 99, 235, 0.20)',
            'page_bg' => '#f4f7fb',
            'ink' => '#172033',
        ];

        return view('portal.login', compact('portalBrand'));
    }

    public function doLogin(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $leads = CrmEmail::with(['workspace', 'salesOrder'])
            ->where('client_email', trim($request->email))
            ->whereNotNull('portal_password')
            ->latest('id')
            ->get();

        $lead = $leads->first(function ($candidate) use ($request) {
            return Hash::check($request->password, $candidate->portal_password);
        });

        if (!$lead) {
            return back()->withErrors(['email' => 'Invalid email or password.'])->withInput();
        }

        // Find the latest sales order for this lead
        $order = SalesOrder::where('crm_email_id', $lead->id)->latest()->first();
        if (!$order) {
            return back()->withErrors(['email' => 'No order found for this account.'])->withInput();
        }

        session([
            'portal_order_id' => $order->id,
            'portal_client_email' => trim($request->email),
            'portal_workspace_slug' => optional($lead->workspace)->slug,
        ]);
        $token = self::getToken($order);

        return redirect('/portal/track/' . $order->id . '?token=' . $token);
    }

    public function logout()
    {
        session()->forget(['portal_order_id', 'portal_client_email']);
        return redirect()->route('portal.login');
    }

    // Show order tracking page (no login required - accessible via order link)
    public function track(Request $request, $orderId)
    {
        $token = $request->query('token');

        $order = SalesOrder::with(['lead.workspace', 'agent', 'productionJob'])->findOrFail($orderId);
        $workspaceId = optional($order->lead)->workspace_id;
        $isAlMassa = optional(optional($order->lead)->workspace)->slug === 'mybox-packaging-app';
        $portalBrand = $this->portalBrand($isAlMassa);
        session(['portal_workspace_slug' => optional(optional($order->lead)->workspace)->slug]);

        // Accept token from URL OR from session (logged in customer)
        $sessionValid = false;
        if (session('portal_client_email')) {
            if ($order->lead && $order->lead->client_email === session('portal_client_email')) {
                $sessionValid = true;
            }
        }
        
        $expected = self::getToken($order);
        $tokenValid = $token === $expected;

        if (!$tokenValid && !$sessionValid) {
            // Redirect to login page instead of 403
            return redirect()->route('portal.login')
                ->with('info', 'Please login to track your order.');
        }

        // Fetch all orders for this customer (if logged in) so they can switch between them
        $allOrders = [];
        if ($sessionValid) {
            $clientEmail = session('portal_client_email');
            $allOrders = SalesOrder::with('lead.workspace')->whereHas('lead', function($q) use ($clientEmail, $workspaceId) {
                $q->where('client_email', $clientEmail)
                    ->where('workspace_id', $workspaceId);
            })->orderBy('created_at', 'desc')->get();
        }

        $lead = $order->lead;
        $job  = $order->productionJob;

        // Build timeline
        $timeline = $this->buildTimeline($order, $job);

        // Chat messages
        $messages = DB::table('customer_chats')
            ->where('sales_order_id', $order->id)
            ->orderBy('created_at', 'asc')
            ->get();

        // Mark agent messages as read (customer is viewing)
        DB::table('customer_chats')
            ->where('sales_order_id', $order->id)
            ->where('sender_type', 'agent')
            ->where('is_read', 0)
            ->update(['is_read' => 1]);

        // Sales agent info
        $agent = $order->sales_agent_id
            ? CrmUser::find($order->sales_agent_id)
            : null;

        $carrierLabels = [
            'ltl_freight' => 'LTL Freight',
            'fedex'       => 'FedEx',
            'dhl'         => 'DHL',
            'usps'        => 'USPS',
            'ups'         => 'UPS',
        ];

        return view('portal.track', compact('order', 'lead', 'job', 'timeline', 'messages', 'agent', 'token', 'carrierLabels', 'allOrders', 'portalBrand'));
    }

    // Customer sends a message
    public function sendMessage(Request $request, $orderId)
    {
        $token = $request->input('token');
        $order = SalesOrder::findOrFail($orderId);

        // Accept token OR session
        $sessionValid = false;
        if (session('portal_client_email')) {
            if ($order->lead && $order->lead->client_email === session('portal_client_email')) {
                $sessionValid = true;
            }
        }

        $expected = self::getToken($order);
        if ($token !== $expected && !$sessionValid) {
            return redirect()->route('portal.login');
        }

        $request->validate(['message' => 'required|string|max:2000']);

        $id = DB::table('customer_chats')->insertGetId([
            'sales_order_id' => $order->id,
            'crm_user_id'    => null,
            'sender_type'    => 'customer',
            'message_body'   => $request->message,
            'is_read'        => 0,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        // The internal message notification is now natively integrated into Team Chat.

        return response()->json(['success' => true, 'id' => $id]);
    }

    // Agent replies from CRM (via AJAX)
    public function agentReply(Request $request, $orderId)
    {
        $user = auth()->guard('crm')->user();
        if (!$user) abort(403);

        $request->validate(['message' => 'required|string|max:2000']);

        $id = DB::table('customer_chats')->insertGetId([
            'sales_order_id' => $orderId,
            'crm_user_id'    => $user->id,
            'sender_type'    => 'agent',
            'message_body'   => $request->message,
            'is_read'        => 0,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $msg = DB::table('customer_chats')->where('id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => $msg,
            'agent_name' => $user->name,
        ]);
    }

    // Get new messages via polling (customer side)
    public function getMessages(Request $request, $orderId)
    {
        $token = $request->query('token');
        $order = SalesOrder::findOrFail($orderId);

        $sessionValid = session('portal_order_id') == $orderId && session('portal_client_email');
        $expected = self::getToken($order);
        if ($token !== $expected && !$sessionValid) {
            return response()->json([]);
        }

        $since = $request->query('since', 0);

        $messages = DB::table('customer_chats')
            ->where('sales_order_id', $orderId)
            ->where('id', '>', $since)
            ->orderBy('created_at', 'asc')
            ->get();

        // Mark agent messages as read
        DB::table('customer_chats')
            ->where('sales_order_id', $orderId)
            ->where('sender_type', 'agent')
            ->where('is_read', 0)
            ->update(['is_read' => 1]);

        return response()->json($messages);
    }

    // CRM agent gets unread customer messages for a specific order
    public function getOrderChatMessages(Request $request, $orderId)
    {
        $user = auth()->guard('crm')->user();
        if (!$user) abort(403);

        $messages = DB::table('customer_chats')
            ->where('sales_order_id', $orderId)
            ->orderBy('created_at', 'asc')
            ->get();

        // Mark customer messages as read
        DB::table('customer_chats')
            ->where('sales_order_id', $orderId)
            ->where('sender_type', 'customer')
            ->where('is_read', 0)
            ->update(['is_read' => 1]);

        return response()->json($messages);
    }

    // Generate the tracking link for a sales order
    public static function trackingUrl(SalesOrder $order): string
    {
        $token = self::getToken($order);
        return url('/portal/track/' . $order->id . '?token=' . $token);
    }

    public static function getToken(SalesOrder $order): string
    {
        return hash('sha256', $order->id . '-' . $order->crm_email_id . '-mybox2024portal');
    }

    private function portalBrand($isAlMassa = false): array
    {
        if ($isAlMassa) {
            return [
                'is_al_massa' => true,
                'name' => 'Al Massa Packaging',
                'short_name' => 'Al Massa',
                'logo' => asset('al-massa-packaging-logo.png'),
                'website' => 'https://almassapackaging.com',
                'support_email' => 'support@almassapackaging.com',
                'support_phone' => '1800-518-9441',
                'support_phone_link' => '18005189441',
                'primary' => '#f45a24',
                'primary_dark' => '#d94314',
                'primary_soft' => '#fff0e8',
                'primary_ring' => 'rgba(244, 90, 36, 0.22)',
                'primary_rgb' => '244, 90, 36',
                'page_bg' => '#f8f5ef',
                'ink' => '#171717',
                'ink_soft' => '#3f3834',
                'muted' => '#766f68',
                'line' => '#f0d9cf',
                'line_strong' => '#f2bda7',
                'paper_soft' => '#fff9f5',
                'dark' => '#171717',
                'dark_2' => '#2b211d',
            ];
        }

        return [
            'is_al_massa' => false,
            'name' => 'MyBox Printing',
            'short_name' => 'MyBox',
            'logo' => asset('my-box-printing-logo.svg'),
            'website' => 'https://www.myboxprinting.com',
            'support_email' => 'support@myboxprinting.com',
            'support_phone' => '847-200-0974',
            'support_phone_link' => '8472000974',
            'primary' => '#7ec832',
            'primary_dark' => '#5aac18',
            'primary_soft' => '#eef9df',
                'primary_ring' => 'rgba(126, 200, 50, 0.22)',
                'primary_rgb' => '126, 200, 50',
                'page_bg' => '#eef5e8',
                'ink' => '#111b0d',
                'ink_soft' => '#263820',
                'muted' => '#6f8068',
                'line' => '#dfead6',
                'line_strong' => '#c8dbbb',
                'paper_soft' => '#f7faf3',
                'dark' => '#10190c',
                'dark_2' => '#1d2b16',
        ];
    }

    private function buildTimeline(SalesOrder $order, $job): array
    {
        $stage = $order->shipping_stage ?: $order->status;

        $steps = [
            ['key' => 'order_placed',       'label' => 'Order Placed',           'icon' => '📋', 'date' => $order->created_at],
            ['key' => 'artwork',             'label' => 'Artwork / Design',       'icon' => '🎨', 'date' => null],
            ['key' => 'prepress',            'label' => 'Prepress / Plate',       'icon' => '🖨️', 'date' => null],
            ['key' => 'production',          'label' => 'In Production',          'icon' => '🏭', 'date' => $job ? $job->actual_start_at : null],
            ['key' => 'quality_control',     'label' => 'Quality Control',        'icon' => '✅', 'date' => null],
            ['key' => 'warehouse',           'label' => 'Warehouse / Ready',      'icon' => '📦', 'date' => null],
            ['key' => 'shipped',             'label' => 'Shipped',                'icon' => '🚚', 'date' => $order->shipped_at],
            ['key' => 'delivered',           'label' => 'Delivered',              'icon' => '🎉', 'date' => $order->delivered_at],
        ];

        // Determine completed steps
        $completedKeys = [];

        $completedKeys[] = 'order_placed';

        if ($order->artwork_file_path || in_array($order->status, ['production', 'in_production', 'pending_artwork']) === false) {
            $completedKeys[] = 'artwork';
        }
        if ($order->prepress_notes || $order->is_plate_created) {
            $completedKeys[] = 'prepress';
        }
        if ($job && in_array($job->status, ['full_production', 'in_process_checks', 'coating_options', 'lamination_options', 'die_cutting', 'stripping', 'blank_separation', 'gluing', 'final_quality_control', 'warehouse_ready', 'production_completed'])) {
            $completedKeys[] = 'production';
        }
        if ($job && in_array($job->status, ['warehouse_ready', 'production_completed'])) {
            $completedKeys[] = 'quality_control';
        }
        if (in_array($stage, ['warehouse_ready', 'balance_payment_check', 'ready_to_ship', 'shipping_department', 'shipping_label_generated', 'in_transit', 'delivered', 'final_invoice', 'payment_posted', 'order_completed'])) {
            $completedKeys[] = 'warehouse';
        }
        if ($order->shipped_at || in_array($stage, ['in_transit', 'delivered', 'final_invoice', 'payment_posted', 'order_completed'])) {
            $completedKeys[] = 'shipped';
        }
        if ($order->delivered_at || in_array($stage, ['delivered', 'final_invoice', 'payment_posted', 'order_completed'])) {
            $completedKeys[] = 'delivered';
        }

        // Determine active step
        $activeKey = end($completedKeys) ?: 'order_placed';

        foreach ($steps as &$step) {
            $step['completed'] = in_array($step['key'], $completedKeys);
            $step['active']    = $step['key'] === $activeKey && !in_array('delivered', $completedKeys);
        }

        return $steps;
    }
}
