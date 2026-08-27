<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SampleOrder extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sample_orders';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'product_id',
        'sample_type',
        'contact_name',
        'contact_phone',
        'contact_email',
        'quantity',
        'shipping_address',
        'billing_address',
        'status',
        'unit_price',
        'delivery_fee',
        'is_price_provided',
    ];

    /**
     * Relationship with user
     */
    public function user()
    {
        return $this->belongsTo('App\User'); // Regular users
    }

    /**
     * Relationship with product (if any)
     */
    public function product()
    {
        return $this->belongsTo('App\Product');
    }
}
