<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EstimateTicketOption extends Model
{
    protected $fillable = [
        'estimate_ticket_id', 'quantity', 'required_date', 'option_notes',
        'total_price', 'unit_price', 'estimator_notes',
        'discount_percentage', 'discounted_price', 'profit_margin_percentage',
        'offer_price', 'offer_unit_price',
    ];

    protected $casts = [
        'required_date' => 'date',
        'discount_percentage' => 'float',
        'discounted_price' => 'float',
        'profit_margin_percentage' => 'float',
    ];

    public function ticket() { return $this->belongsTo(EstimateTicket::class, 'estimate_ticket_id'); }
}
