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
        'receive_date', 'client_approval_date', 'due_date',
    ];

    protected $casts = [
        'status_updated_at' => 'datetime',
        'estimated_delivery_date' => 'date',
        'receive_date' => 'date',
        'client_approval_date' => 'date',
        'due_date' => 'date',
    ];

    /**
     * Due-date urgency meta for colour coding.
     * overdue/today -> red, 1 day left -> orange, 2 days left -> yellow, else -> normal.
     * Returns [level, color, bg, label] or null when there is no due date.
     */
    public function dueMeta(): ?array
    {
        if (!$this->due_date) {
            return null;
        }
        $days = (int) \Carbon\Carbon::today()->diffInDays($this->due_date->copy()->startOfDay(), false);
        if ($days <= 0) {
            return ['level' => 'overdue', 'color' => '#b91c1c', 'bg' => '#fee2e2', 'label' => $days === 0 ? 'Due today' : abs($days) . 'd overdue'];
        }
        if ($days === 1) {
            return ['level' => 'orange', 'color' => '#c2410c', 'bg' => '#ffedd5', 'label' => '1 day left'];
        }
        if ($days === 2) {
            return ['level' => 'yellow', 'color' => '#a16207', 'bg' => '#fef9c3', 'label' => '2 days left'];
        }

        return ['level' => 'ok', 'color' => '#15803d', 'bg' => '#dcfce7', 'label' => $days . ' days left'];
    }

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
