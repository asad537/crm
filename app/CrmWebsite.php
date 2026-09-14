<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CrmWebsite extends Model
{
    protected $table = 'crm_websites';
    protected $fillable = ['name', 'color', 'is_active', 'created_by'];
    protected $casts = ['is_active' => 'boolean'];
}
