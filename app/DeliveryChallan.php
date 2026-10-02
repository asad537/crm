<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DeliveryChallan extends Model
{
    protected $fillable = [
        'workspace_id', 'design_job_id', 'challan_no', 'challan_date', 'delivery_date',
        'job_no', 'vehicle_no', 'customer_name', 'contact_person', 'delivery_address',
        'po_reference', 'items', 'remarks', 'prepared_by', 'driver_name',
        'driver_contact', 'received_by',
    ];

    protected $casts = [
        'challan_date' => 'date',
        'delivery_date' => 'date',
        'items' => 'array',
    ];

    public function designJob()
    {
        return $this->belongsTo(DesignJob::class);
    }
}
