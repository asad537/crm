<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DesignJob extends Model
{
    // Production workflow (matches the shop-floor flow chart).
    const STATUSES = [
        'designing'   => 'Designing',
        'mockup'      => 'Mock Up',
        'printing'    => 'Printing',
        'lamination'  => 'Lamination',
        'embossing'   => 'Embossing',
        'debossing'   => 'Debossing',
        'foiling'     => 'Foiling',
        'die_cutting' => 'Die-Cutting',
        'pasting'     => 'Pasting',
        'packing'     => 'Packing',
        'shipped'     => 'Shipped',
        'delivered'   => 'Delivered',
    ];

    protected $fillable = [
        'job_number', 'workspace_id', 'estimate_ticket_id', 'estimate_number', 'designer_id',
        'title', 'details', 'status', 'status_updated_at', 'estimated_delivery_date',
    ];

    protected $casts = [
        'status_updated_at' => 'datetime',
        'estimated_delivery_date' => 'date',
    ];

    public function ticket()
    {
        return $this->belongsTo(EstimateTicket::class, 'estimate_ticket_id')->withoutGlobalScopes();
    }

    public function designer()
    {
        return $this->belongsTo(CrmUser::class, 'designer_id');
    }

    public function workspace()
    {
        return $this->belongsTo(CrmWorkspace::class, 'workspace_id');
    }

    public function statusLabel()
    {
        return self::STATUSES[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    /** Zero-based index of the current status within the workflow (-1 if unknown). */
    public function statusIndex()
    {
        $keys = array_keys(self::STATUSES);
        $pos = array_search($this->status, $keys, true);
        return $pos === false ? -1 : $pos;
    }

    /** Progress percentage across the workflow. */
    public function progressPercent()
    {
        $total = count(self::STATUSES) - 1;
        $idx = $this->statusIndex();
        if ($total <= 0 || $idx < 0) {
            return 0;
        }
        return (int) round(($idx / $total) * 100);
    }

    /** The estimate reference to display (linked ticket number or manual number). */
    public function estimateRef()
    {
        if ($this->ticket) {
            return $this->ticket->ticket_number;
        }
        return $this->estimate_number ?: null;
    }
}
