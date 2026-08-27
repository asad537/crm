<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CustomProject extends Model
{
    protected $table = 'custom_projects';

    protected $fillable = [
        'project_name',
        'project_description',
        'category_name',
        'subcategory_name',
        'subcategory_image',
        'product_name',
        'product_image',
        'material_name',
        'material_image',
        'addon_name',
        'addon_image',
        'unit',
        'width',
        'height',
        'length',
        'message',
        'user_id',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function dielines()
    {
        return $this->hasMany(Dieline::class, 'project_id');
    }

    public function sampleOrder()
    {
        return $this->hasOne(SampleOrder::class, 'product_id');
    }

    public function productionOrders()
    {
        return $this->hasMany(ProductionOrder::class, 'project_id');
    }
}
