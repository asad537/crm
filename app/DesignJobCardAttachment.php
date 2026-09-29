<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DesignJobCardAttachment extends Model
{
    protected $fillable = ['path', 'original_name', 'mime', 'size'];

    public function jobCard()
    {
        return $this->belongsTo(DesignJobCard::class, 'design_job_card_id');
    }
}
