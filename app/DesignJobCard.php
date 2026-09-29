<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DesignJobCard extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'job_date' => 'date',
        'job_start_on' => 'date',
        'priority_date' => 'date',
        'dummy_sent_on' => 'date',
        'dummy_approved_on' => 'date',
        'section_choices' => 'array',
        'priority_urgent' => 'boolean',
        'priority_critical' => 'boolean',
        'priority_substandard' => 'boolean',
        'coating_uv' => 'boolean',
        'coating_coating' => 'boolean',
        'coating_varnish' => 'boolean',
        'coating_other' => 'boolean',
        'lam_gloss' => 'boolean',
        'lam_matte' => 'boolean',
        'lam_soft_touch' => 'boolean',
        'lam_other' => 'boolean',
        'screen_uv' => 'boolean',
        'foil_gold' => 'boolean',
        'foil_silver' => 'boolean',
        'foil_other' => 'boolean',
        'die_full' => 'boolean',
        'die_half' => 'boolean',
        'die_embossing' => 'boolean',
        'die_debossing' => 'boolean',
        'paste_tape' => 'boolean',
        'paste_glue' => 'boolean',
        'paste_double' => 'boolean',
        'paste_pvc_window' => 'boolean',
        'paste_other' => 'boolean',
    ];

    public function designJob()
    {
        return $this->belongsTo(DesignJob::class);
    }

    public function materials()
    {
        return $this->hasMany(DesignJobCardMaterial::class)->orderBy('position');
    }

    public function stocks()
    {
        return $this->hasMany(DesignJobCardStock::class)->orderBy('position');
    }
}
