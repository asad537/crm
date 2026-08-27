<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalesOrder extends Model
{
    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('crm_workspace', function ($query) {
            if (\App\Support\CrmWorkspaceContext::id()) {
                $query->whereHas('lead');
            }
        });
    }

    protected $fillable = [
        'crm_email_id',
        'sales_agent_id',
        'payment_term',
        'credit_days',
        'payment_status',
        'status',
        'shipping_stage',
        'customer_type',
        'shipping_carrier',
        'tracking_number',
        'balance_received_at',
        'final_payment_received_at',
        'label_generated_at',
        'shipped_at',
        'delivered_at',
        'final_invoice_sent_at',
        'payment_posted_at',
        'order_completed_at',
        'retention_follow_up_at',
        'reorder_reminder_at',
        'shipping_notes',
        'accounts_notes',
        'shipping_receipt_path',
        'artwork_file_path',
        'design_notes',
        'prepress_checks',
        'prepress_notes',
        'prepress_revision_attachment',
        'sales_revision_attachment',
        'is_plate_created',
        'production_facility_id',
    ];

    protected $casts = [
        'prepress_checks' => 'array',
        'balance_received_at' => 'datetime',
        'final_payment_received_at' => 'datetime',
        'label_generated_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'final_invoice_sent_at' => 'datetime',
        'payment_posted_at' => 'datetime',
        'order_completed_at' => 'datetime',
        'retention_follow_up_at' => 'datetime',
        'reorder_reminder_at' => 'datetime',
    ];

    public function lead()
    {
        return $this->belongsTo(CrmEmail::class, 'crm_email_id');
    }

    public function agent()
    {
        return $this->belongsTo(CrmUser::class, 'sales_agent_id');
    }

    public function productionJob()
    {
        return $this->hasOne(ProductionJob::class);
    }
}
