<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DesignJobCardStock extends Model
{
    protected $guarded = ['id'];

    public function jobCard()
    {
        return $this->belongsTo(DesignJobCard::class, 'design_job_card_id');
    }
}
