<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/** "Send to Production" job briefing for a paid TCB manual order. Write-once (admins may edit). */
class CrmManualOrderProductionBrief extends Model
{
    protected $table = 'crm_manual_order_production_briefs';

    protected $fillable = [
        'manual_order_id', 'workspace_id', 'job_number', 'brief_date', 'production_type', 'job_type', 'client_name',
        'products', 'folder_path', 'job_forwarding_date', 'printers_deadline', 'clients_deadline',
        'additional_requirements', 'status', 'sent_by', 'sent_at', 'updated_by',
    ];

    protected $casts = [
        'products' => 'array',
        'brief_date' => 'date',
        'job_forwarding_date' => 'date',
        'printers_deadline' => 'date',
        'clients_deadline' => 'date',
        'sent_at' => 'datetime',
    ];

    public const PRODUCTION_TYPES = [
        'actual' => 'Actual',
        'reprint' => 'Reprint',
        'sample' => 'Sample',
    ];

    public const JOB_TYPES = ['Standard', 'Rush'];

    /** Per-product fields, in display order: key => label. */
    public const PRODUCT_FIELDS = [
        'product' => 'Product',
        'material' => 'Material',
        'quantity' => 'Quantity',
        'finish_size' => 'Finish Size',
        'lamination' => 'Lamination',
        'printing' => 'Printing',
        'diecut_window' => 'Diecut Window',
        'plastic_film' => 'Plastic Film',
        'pasting' => 'Pasting',
        'spot_uv' => 'Spot UV',
        'deboss_emboss' => 'Deboss / Emboss',
        'raised_ink_foiling' => 'Raised Ink / Foiling',
        'additional_requirements' => 'Additional Requirements',
    ];

    public function order()
    {
        return $this->belongsTo(CrmManualOrder::class, 'manual_order_id');
    }

    public function sender()
    {
        return $this->belongsTo(CrmUser::class, 'sent_by');
    }

    public function productionTypeLabel(): string
    {
        return self::PRODUCTION_TYPES[$this->production_type] ?? (string) $this->production_type;
    }
}
