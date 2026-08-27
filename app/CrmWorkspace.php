<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CrmWorkspace extends Model
{
    protected $fillable = ['name', 'slug', 'api_key_hash', 'is_active'];
    protected $hidden = ['api_key_hash'];
    protected $casts = ['is_active' => 'boolean'];

    public function users()
    {
        return $this->belongsToMany(CrmUser::class, 'crm_user_workspace', 'workspace_id', 'crm_user_id')
            ->withPivot('role')->withTimestamps();
    }
}
