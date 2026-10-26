<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/** Per-workspace key / value settings (e.g. WPS establishment ID). */
class CrmSetting extends Model
{
    protected $table = 'crm_settings';

    protected $fillable = ['workspace_id', 'key', 'value'];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('crm_workspace', function ($query) {
            if ($id = \App\Support\CrmWorkspaceContext::id()) {
                $query->where($query->getModel()->getTable() . '.workspace_id', $id);
            }
        });
        static::creating(function ($m) {
            if (!$m->workspace_id && ($id = \App\Support\CrmWorkspaceContext::id())) $m->workspace_id = $id;
        });
    }

    public static function get(string $key, $default = null)
    {
        $value = static::where('key', $key)->value('value');

        return $value === null ? $default : $value;
    }

    public static function put(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key, 'workspace_id' => \App\Support\CrmWorkspaceContext::id()], ['value' => $value]);
    }
}
