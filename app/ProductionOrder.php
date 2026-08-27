<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductionOrder extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'production_orders';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'project_id',
        'user_id',
        'production_type', // plain, printed
        'contact_name',
        'contact_phone',
        'contact_email',
        'quantity',
        'shipping_address',
        'billing_address',
        'status', // pending_review, payment_pending, in_production, quality_check, shipped, delivered, cancelled
        'unit_price',
        'delivery_fee',
        'is_price_provided',
        'admin_note',
    ];

    /**
     * Relationship with user
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    /**
     * Relationship with project
     */
    public function project()
    {
        return $this->belongsTo('App\CustomProject', 'project_id');
    }
}
