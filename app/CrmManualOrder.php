<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CrmManualOrder extends Model
{
    protected $table = 'crm_manual_orders';

    protected $fillable = [
        'workspace_id', 'crm_email_id', 'crm_customer_id', 'user_name', 'enquiry_number', 'invoice_status',
        'invoice_number', 'website', 'currency', 'invoice_date', 'customer_id', 'payment_term',
        'billing', 'shipping', 'sales_person', 'shipping_method', 'shipping_term',
        'payment_term_via', 'additional_info', 'line_items', 'sub_total', 'package_price',
        'rush_charges', 'discount', 'total', 'created_by',
        'paypal_invoice_id', 'paypal_invoice_number', 'paypal_invoice_status', 'paypal_invoice_url',
        'paypal_sent_to', 'paypal_sent_at', 'paypal_send_count',
        'cca_sent_to', 'cca_sent_at', 'cca_send_count',
        'invoice_sent_to', 'invoice_sent_at', 'invoice_send_count',
    ];

    protected $casts = [
        'billing' => 'array',
        'shipping' => 'array',
        'line_items' => 'array',
        'invoice_date' => 'date',
        'paypal_sent_at' => 'datetime',
        'cca_sent_at' => 'datetime',
        'invoice_sent_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('workspace', function ($query) {
            if ($id = \App\Support\CrmWorkspaceContext::id()) {
                $query->where($query->getModel()->getTable() . '.workspace_id', $id);
            }
        });
        static::creating(function ($model) {
            if (!$model->workspace_id && ($id = \App\Support\CrmWorkspaceContext::id())) {
                $model->workspace_id = $id;
            }
        });
    }

    /** Linked inquiry (source lead), if the order was created from one. */
    public function inquiry()
    {
        return $this->belongsTo(CrmEmail::class, 'crm_email_id');
    }

    /** Linked customer from the Customers tab, if one was selected on the order. */
    public function customer()
    {
        return $this->belongsTo(CrmCustomer::class, 'crm_customer_id');
    }

    /**
     * Best known customer email, in priority order:
     * billing email on the order -> linked Customers-tab record -> linked inquiry's client email.
     */
    public function customerEmail(): ?string
    {
        $email = trim((string) data_get($this->billing, 'email'));
        if ($email === '' && $this->crm_customer_id) {
            $email = trim((string) optional($this->customer)->email);
        }
        if ($email === '' && $this->crm_email_id) {
            $email = trim((string) optional($this->inquiry)->client_email);
        }
        return $email !== '' ? $email : null;
    }

    public function payments()
    {
        return $this->hasMany(CrmManualOrderPayment::class, 'manual_order_id')->orderBy('paid_at')->orderBy('id');
    }

    public function paidAmount(): float
    {
        return round((float) $this->payments->sum('amount'), 2);
    }

    public function balanceDue(): float
    {
        return round(max(0, (float) $this->total - $this->paidAmount()), 2);
    }

    /** Mark paid when payments cover the total, else unpaid. */
    public function refreshPaymentStatus(): void
    {
        $this->unsetRelation('payments');
        $this->invoice_status = ((float) $this->total > 0 && $this->balanceDue() <= 0.009) ? 'paid' : 'unpaid';
        $this->save();
    }

    /**
     * Pull the PayPal invoice status and record any money PayPal shows as paid.
     * Returns the PayPal status, or null when the order has no PayPal invoice.
     */
    public function syncPaypal(?\App\Services\PaypalInvoiceService $svc = null): ?string
    {
        if (!$this->paypal_invoice_id) return null;
        $svc = $svc ?: new \App\Services\PaypalInvoiceService();
        $inv = $svc->getInvoice($this->paypal_invoice_id);

        $this->paypal_invoice_status = $inv['status'];

        // PayPal-reported paid amount minus what we already recorded for this invoice.
        $recorded = (float) $this->payments()->where('gateway', 'paypal')
            ->where('gateway_transaction_id', $this->paypal_invoice_id)->sum('amount');
        $paid = $inv['paid'];
        if ($paid <= 0 && in_array($inv['status'], ['PAID', 'MARKED_AS_PAID'], true)) {
            $paid = $inv['total'] > 0 ? $inv['total'] : (float) $this->total;
        }
        $delta = round($paid - $recorded, 2);
        if ($delta > 0.009) {
            $this->payments()->create([
                'workspace_id' => $this->workspace_id,
                'amount' => min($delta, max(0, $this->balanceDue())) ?: $delta,
                'paid_at' => now()->toDateString(),
                'method' => 'PayPal',
                'reference' => $this->paypal_invoice_id,
                'note' => 'PayPal invoice ' . ($this->paypal_invoice_number ?: $this->paypal_invoice_id) . ' · ' . $inv['status'],
                'created_by' => optional(\Illuminate\Support\Facades\Auth::guard('crm')->user())->id,
                'gateway' => 'paypal',
                'gateway_transaction_id' => $this->paypal_invoice_id,
                'gateway_response' => json_encode(['status' => $inv['status'], 'paid' => $inv['paid'], 'total' => $inv['total'], 'currency' => $inv['currency']]),
            ]);
        }
        $this->refreshPaymentStatus(); // also saves paypal_invoice_status
        return $inv['status'];
    }

    /** Production job briefing created from "Send to Production" (one per order). */
    public function productionBrief()
    {
        return $this->hasOne(CrmManualOrderProductionBrief::class, 'manual_order_id');
    }

    public function printReadyTicket()
    {
        return $this->hasOne(CrmPrintReadyTicket::class, 'manual_order_id')->latestOfMany();
    }

    public function creator()
    {
        return $this->belongsTo(CrmUser::class, 'created_by');
    }
}
