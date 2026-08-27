<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Mail\OrderShippedMail;
use App\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class FulfillmentController extends Controller
{
    private $stageLabels = [
        'warehouse_ready' => 'Warehouse / Ready to Ship',
        'balance_payment_check' => 'Balance Payment Check',
        'final_payment_pending' => 'Final Payment Pending',
        'ready_to_ship' => 'Ready to Ship',
        'shipping_department' => 'Shipping Department',
        'shipping_label_generated' => 'Shipping Label Generated',
        'in_transit' => 'In Transit',
        'delivered' => 'Delivered',
        'final_invoice' => 'Invoice / Final Invoice',
        'payment_posted' => 'Payment Posted',
        'order_completed' => 'Order Completed',
        'retention_follow_up' => 'Customer Success Follow-up',
        'reorder_reminder' => 'Reorder Reminder / Upsell / Retention',
    ];

    public function warehouseTickets()
    {
        $user = $this->authorizedUser();
        if (!$user->isAdmin() && !$user->isProductionManager() && !$user->isWarehouse()) {
            abort(403, 'Only Warehouse, Production Manager or Admin can view warehouse tickets.');
        }

        return $this->renderBoard(['warehouse_ready', null], [
            'pageTitle' => 'Warehouse Tickets',
            'pageEyebrow' => 'Warehouse',
            'pageSubtitle' => 'Production-completed jobs waiting for payment check and shipping handoff.',
            'queueTitle' => 'Warehouse Ready Queue',
        ]);
    }

    public function accountsTickets()
    {
        $user = $this->authorizedUser();
        if (!$user->isAdmin() && !$user->isSalesManager() && !$user->isAccounts()) {
            abort(403, 'Only Accounts, Sales Manager or Admin can view accounts tickets.');
        }

        return $this->renderBoard(['pending_payment', 'balance_payment_check', 'final_payment_pending', 'delivered', 'final_invoice', 'payment_posted'], [
            'pageTitle' => 'Accounts Tickets',
            'pageEyebrow' => 'Accounts',
            'pageSubtitle' => 'Initial payment approvals, balance payment checks, credit terms, final invoice and payment posting.',
            'queueTitle' => 'Accounts Queue',
        ]);
    }

    public function shippingTickets()
    {
        $user = $this->authorizedUser();
        if (!$user->isAdmin() && !$user->isSalesManager() && !$user->isShipping()) {
            abort(403, 'Only Shipping, Sales Manager or Admin can view shipping tickets.');
        }

        return $this->renderBoard(['ready_to_ship', 'shipping_department', 'shipping_label_generated', 'in_transit'], [
            'pageTitle' => 'Shipping Tickets',
            'pageEyebrow' => 'Shipping Department',
            'pageSubtitle' => 'Carrier selection, shipping labels, tracking numbers, in-transit and delivery updates.',
            'queueTitle' => 'Ready to Ship Queue',
        ]);
    }

    public function retentionTickets()
    {
        $user = $this->authorizedUser();
        if (!$user->isAdmin() && !$user->isSalesManager() && !$user->isRetention()) {
            abort(403, 'Only Retention, Sales Manager or Admin can view retention tickets.');
        }

        return $this->renderBoard(['order_completed', 'completed', 'complete', 'retention_follow_up', 'reorder_reminder'], [
            'pageTitle' => 'Retention Tickets',
            'pageEyebrow' => 'Customer Success',
            'pageSubtitle' => 'Post-delivery follow-up, reorder reminders, upsell and retention workflow.',
            'queueTitle' => 'Customer Success Queue',
        ], true);
    }

    public function updateStage(Request $request, $id)
    {
        $user = $this->authorizedUser();
        $order = SalesOrder::with('lead')->findOrFail($id);
        $currentStage = $order->shipping_stage ?: $order->status;

        $request->validate([
            'action' => 'required|in:initial_payment_decision,send_to_payment_check,payment_decision,shipping_label,in_transit,delivered,final_invoice,payment_posted,order_completed,retention_follow_up,reorder_reminder,upload_receipt',
            'customer_type' => 'nullable|in:credit,non_credit',
            'balance_received' => 'nullable|boolean',
            'final_payment_received' => 'nullable|boolean',
            'shipping_carrier' => 'nullable|in:ltl_freight,fedex,dhl,usps,ups',
            'tracking_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $data = [];
        $message = 'Fulfillment stage updated.';

        switch ($request->action) {
            case 'initial_payment_decision':
                $this->requireRole($user, ['admin', 'sales_manager', 'accounts']);
                $this->requireStage($currentStage, ['pending_payment']);
                
                $data['payment_status'] = $order->payment_term === 'credit' ? 'approved' : 'received';
                $data['status'] = 'pending_artwork';
                
                if ($order->lead) {
                    $order->lead->update(['payment_status' => 'Paid']);
                }
                
                \App\Services\WorkflowService::logApproval(
                    $order->lead,
                    'payment_cleared',
                    'approved',
                    'Payment/Credit cleared by Accounts. Waiting for artwork upload.'
                );
                
                $message = 'Initial payment approved. Order moved to Needs Artwork.';
                break;

            case 'send_to_payment_check':
                $this->requireRole($user, ['admin', 'production_manager', 'warehouse']);
                $this->requireStage($currentStage, ['warehouse_ready', 'production_completed']);
                $data['shipping_stage'] = 'balance_payment_check';
                $message = 'Sent to Accounts for balance payment check.';
                break;

            case 'payment_decision':
                $this->requireRole($user, ['admin', 'sales_manager', 'accounts']);
                $this->requireStage($currentStage, ['balance_payment_check', 'final_payment_pending']);
                
                $term = $order->payment_term;
                
                if ($term === 'credit') {
                    if (!$request->has('credit_approved')) {
                        return back()->with('error', 'Confirm credit approval before shipping.');
                    }
                    $data['shipping_stage'] = 'ready_to_ship';
                    $data['payment_status'] = 'approved';
                    $message = 'Credit terms approved for shipping.';
                    break;
                }
                
                if ($term === '100_deposit') {
                    if (!$request->has('final_payment_received')) {
                        return back()->with('error', 'Verify 100% deposit received before shipping.');
                    }
                    $data['balance_received_at'] = now();
                    $data['final_payment_received_at'] = now();
                    $data['shipping_stage'] = 'ready_to_ship';
                    $data['payment_status'] = 'received';
                    $message = '100% deposit verified. Ready to ship.';
                    break;
                }
                
                // For 50_advance or other
                if (!$request->has('final_payment_received')) {
                    return back()->with('error', 'Confirm final 50% balance received before shipping.');
                }
                $data['balance_received_at'] = $order->created_at; // Advance was earlier
                $data['final_payment_received_at'] = now();
                $data['shipping_stage'] = 'ready_to_ship';
                $data['payment_status'] = 'received';
                $message = 'Final 50% balance received. Ready to ship.';
                break;

            case 'shipping_label':
                $this->requireRole($user, ['admin', 'sales_manager', 'shipping']);
                $this->requireStage($currentStage, ['ready_to_ship', 'shipping_department']);
                if (!$request->shipping_carrier || !$request->tracking_number) {
                    return back()->with('error', 'Carrier and tracking number are required.');
                }
                $data['shipping_stage'] = 'shipping_label_generated';
                $data['shipping_carrier'] = $request->shipping_carrier;
                $data['tracking_number'] = $request->tracking_number;
                $data['label_generated_at'] = now();
                // Handle receipt upload
                if ($request->hasFile('receipt')) {
                    $path = $request->file('receipt')->store('shipping_receipts', 'public');
                    $data['shipping_receipt_path'] = $path;
                }
                $message = 'Shipping label generated and tracking number saved.';
                break;

            case 'upload_receipt':
                $this->requireRole($user, ['admin', 'sales_manager', 'shipping']);
                if (!$request->hasFile('receipt')) {
                    return back()->with('error', 'Please select a receipt file to upload.');
                }
                $path = $request->file('receipt')->store('shipping_receipts', 'public');
                $order->update(['shipping_receipt_path' => $path]);
                return back()->with('success', 'Receipt uploaded successfully.');

            case 'in_transit':
                $this->requireRole($user, ['admin', 'sales_manager', 'shipping']);
                $this->requireStage($currentStage, ['shipping_label_generated']);
                $data['shipping_stage'] = 'in_transit';
                $data['shipped_at'] = now();

                // Send shipped notification to client
                $order->load('lead');
                $clientEmail = $order->lead ? $order->lead->client_email : null;
                if ($clientEmail) {
                    try {
                        Mail::to($clientEmail)->send(new OrderShippedMail($order, $request->notes));
                    } catch (\Exception $e) {
                        // Don't fail if email doesn't send
                        \Log::warning('Order shipped email failed: ' . $e->getMessage());
                    }
                }

                $message = 'Customer notified. Shipment marked in transit.';
                break;

            case 'delivered':
                $this->requireRole($user, ['admin', 'sales_manager', 'shipping']);
                $this->requireStage($currentStage, ['in_transit']);
                $data['shipping_stage'] = 'delivered';
                $data['delivered_at'] = now();
                $message = 'Shipment marked delivered.';
                break;

            case 'final_invoice':
                $this->requireRole($user, ['admin', 'sales_manager', 'accounts']);
                $this->requireStage($currentStage, ['delivered']);
                $data['shipping_stage'] = 'final_invoice';
                $data['final_invoice_sent_at'] = now();
                $message = 'Final invoice recorded.';
                break;

            case 'payment_posted':
                $this->requireRole($user, ['admin', 'sales_manager', 'accounts']);
                $this->requireStage($currentStage, ['final_invoice']);
                $data['shipping_stage'] = 'payment_posted';
                $data['payment_status'] = 'received';
                $data['payment_posted_at'] = now();
                $message = 'Payment posted.';
                break;

            case 'order_completed':
                $this->requireRole($user, ['admin', 'sales_manager', 'accounts']);
                $this->requireStage($currentStage, ['payment_posted']);
                $data['shipping_stage'] = 'order_completed';
                $data['status'] = 'order_completed';
                $data['order_completed_at'] = now();
                $message = 'Order completed.';
                break;

            case 'retention_follow_up':
                $this->requireRole($user, ['admin', 'sales_manager', 'retention']);
                $this->requireStage($currentStage, ['order_completed']);
                $data['shipping_stage'] = 'retention_follow_up';
                $data['retention_follow_up_at'] = now();
                $message = 'Customer success follow-up recorded.';
                break;

            case 'reorder_reminder':
                $this->requireRole($user, ['admin', 'sales_manager', 'retention']);
                $this->requireStage($currentStage, ['retention_follow_up']);
                $data['shipping_stage'] = 'reorder_reminder';
                $data['reorder_reminder_at'] = now();
                $message = 'Reorder reminder / upsell step recorded.';
                break;
        }

        if ($request->filled('notes')) {
            if (in_array($request->action, ['payment_decision', 'final_invoice', 'payment_posted'], true)) {
                $data['accounts_notes'] = trim(($order->accounts_notes ? $order->accounts_notes . "\n" : '') . $request->notes);
            } else {
                $data['shipping_notes'] = trim(($order->shipping_notes ? $order->shipping_notes . "\n" : '') . $request->notes);
            }
        }

        if (!empty($data['shipping_stage'])) {
            $data['status'] = $data['status'] ?? $data['shipping_stage'];
        }

        $order->update($data);
        if ($request->action === 'order_completed' && $order->lead) {
            $order->lead->update(['status' => 'Completed', 'estimate_status' => 'completed']);
        }

        return back()->with('success', $message);
    }

    private function renderBoard(array $stages, array $viewData, $includeCompletedOrders = false)
    {
        $query = SalesOrder::with(['lead', 'agent', 'productionJob'])
            ->where(function ($query) use ($stages, $includeCompletedOrders) {
                foreach ($stages as $stage) {
                    if ($stage === null) {
                        $query->orWhere(function ($nested) {
                            $nested->whereNull('shipping_stage')->where('status', 'warehouse_ready');
                        });
                    } else {
                        $query->orWhere(function ($nested) use ($stage) {
                            $nested->where('shipping_stage', $stage)->orWhere('status', $stage);
                        });
                    }
                }

                if ($includeCompletedOrders) {
                    $query->orWhereNotNull('order_completed_at');
                }
            });

        return view('crm.fulfillment.index', $viewData + [
            'orders' => $query->orderBy('updated_at', 'desc')->get(),
            'stageLabels' => $this->stageLabels,
        ]);
    }

    private function authorizedUser()
    {
        $user = Auth::guard('crm')->user();
        if (!$user || (!$user->isAdmin() && !$user->isSalesManager() && !$user->isProductionManager() && !$user->isWarehouse() && !$user->isAccounts() && !$user->isShipping() && !$user->isRetention())) {
            abort(403, 'Unauthorized fulfillment access.');
        }
        return $user;
    }

    private function requireRole($user, array $roles)
    {
        $allowed = in_array('admin', $roles, true) && $user->isAdmin()
            || in_array('sales_manager', $roles, true) && $user->isSalesManager()
            || in_array('production_manager', $roles, true) && $user->isProductionManager()
            || in_array('warehouse', $roles, true) && $user->isWarehouse()
            || in_array('accounts', $roles, true) && $user->isAccounts()
            || in_array('shipping', $roles, true) && $user->isShipping()
            || in_array('retention', $roles, true) && $user->isRetention();

        if (!$allowed) {
            abort(403, 'You are not allowed to perform this fulfillment action.');
        }
    }

    private function requireStage($currentStage, array $allowed)
    {
        if (!in_array($currentStage, $allowed, true)) {
            abort(422, 'This action is not allowed while the order is ' . str_replace('_', ' ', $currentStage) . '.');
        }
    }
}
