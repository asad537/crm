<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmEmail extends Model
{
    use SoftDeletes;

    protected $table = 'crm_emails';

    protected $fillable = [
        'workspace_id',
        'created_by',
        'external_lead_id',
        'imap_message_id',
        'source',
        'product_name',
        'order_invoice_number',
        'client_name',
        'client_email',
        'client_phone',
        'customer_trn',
        'company_trn',
        'trade_license_number',
        'length',
        'width',
        'height',
        'unit',
        'stock',
        'color',
        'coating',
        'quantity',
        'file_url',
        'message',
        'subject',
        'ip_address',
        'country',
        'is_spam',
        'is_rejected',
        'rejection_note',
        'rejected_at',
        'rejected_by',
        'spam_reason',
        'status', // Added
        'production_status',
        'linkedin_url',
        'twitter_url',
        'facebook_url',
        'instagram_url',
        'social_investigated_at',
        'order_price',
        'order_quantity',
        'order_notes',
        'order_marked_at',
        'order_marked_by',
        'assigned_to',
        'assigned_by',
        'assigned_at',
        'lamination',
        'die',
        'glue',
        'shipping_region',
        'changed_specs',
        'custom_specs',
        'estimator_id',
        'estimate_status',
        'estimated_price',
        'discount',
        'waste_material_percentage',
        'waste_material_amount',
        'estimator_notes',
        'sales_agent_notes',
        'estimate_breakdown',
        'estimate_quantity_options',
        'payment_status',
        'invoice_currency',
        'vat_percentage',
        'billing_address',
        'shipping_address',
        'portal_password',
        'printing',
        'finish_size',
        'open_size',
        'csr_comment',
        'website',
        'price_offered',
        'inquiry_quantities',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('crm_workspace', function ($query) {
            if ($workspaceId = \App\Support\CrmWorkspaceContext::id()) {
                $query->where($query->getModel()->getTable() . '.workspace_id', $workspaceId);
            }
        });

        static::creating(function ($email) {
            if (!$email->workspace_id && ($workspaceId = \App\Support\CrmWorkspaceContext::id())) {
                $email->workspace_id = $workspaceId;
            }
        });
    }

    public function workspace()
    {
        return $this->belongsTo(CrmWorkspace::class, 'workspace_id');
    }

    protected $casts = [
        'is_rejected' => 'boolean',
        'rejected_at' => 'datetime',
        'changed_specs' => 'array',
        'custom_specs' => 'array',
        'estimate_breakdown' => 'array',
        'estimate_quantity_options' => 'array',
        'inquiry_quantities' => 'array',
    ];

    public function statusLogs()
    {
        return $this->hasMany(CrmStatusLog::class, 'crm_email_id');
    }

    public function messages()
    {
        return $this->hasMany(CrmMessage::class, 'crm_email_id')->orderBy('created_at', 'asc');
    }

    public function orderItems()
    {
        return $this->hasMany(CrmOrderItem::class, 'crm_email_id');
    }

    public function latestMessage()
    {
        return $this->hasOne(CrmMessage::class, 'crm_email_id')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');
    }

    public function proofRevisions()
    {
        return $this->hasMany(ProofRevision::class, 'crm_email_id')->orderBy('version_number', 'desc');
    }

    public function inquiryAttachments()
    {
        return $this->hasMany(InquiryAttachment::class, 'crm_email_id');
    }

    public function designRequirementTicket()
    {
        return $this->hasOne(DesignRequirementTicket::class, 'crm_email_id');
    }

    public function qualityControls()
    {
        return $this->hasMany(QualityControl::class, 'crm_email_id');
    }

    public function approvals()
    {
        return $this->morphMany(CrmApproval::class, 'approvable');
    }

    public function getCustomerTypeAttribute()
    {
        // Simple optimization: If we've already loaded this, return it.
        // Logic: specific to this instance context?
        if (!$this->client_email) return 'N';

        // Check for any previous emails from this client
        $exists = CrmEmail::where('client_email', $this->client_email)
                          ->where('id', '<', $this->id)
                          ->exists();

        return $exists ? 'RC' : 'N';
    }

    public function getWorkflowNumberAttribute()
    {
        if (!$this->exists || !$this->id) {
            return null;
        }

        return 'INQ-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    public function estimator()
    {
        return $this->belongsTo('App\CrmUser', 'estimator_id');
    }

    public function salesOrder()
    {
        return $this->hasOne(\App\SalesOrder::class, 'crm_email_id');
    }

    public function estimateTickets()
    {
        return $this->hasMany(\App\EstimateTicket::class, 'crm_email_id')->latest('id');
    }

    public function rejectionLog()
    {
        return $this->hasOne(\App\CrmRejectionLog::class, 'crm_email_id');
    }
}
