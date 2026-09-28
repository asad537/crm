<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DesignJobCardMaterial extends Model
{
    protected $guarded = ['id'];

    protected $dates = ['needed_by'];

    public function jobCard()
    {
        return $this->belongsTo(DesignJobCard::class, 'design_job_card_id');
    }
}
