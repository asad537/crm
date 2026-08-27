<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductionFacility extends Model
{
    protected $fillable = ['name', 'city', 'country', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function machines()
    {
        return $this->hasMany(ProductionMachine::class);
    }
}
