<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductionMachine extends Model
{
    protected $fillable = [
        'production_facility_id', 'name', 'code', 'printing_method', 'status',
    ];

    public function facility()
    {
        return $this->belongsTo(ProductionFacility::class, 'production_facility_id');
    }
}
