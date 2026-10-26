<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/** An owner / partner of the business, with a capital account and a drawings account (Equity in the chart of accounts). */
class CrmCapitalOwner extends Model
{
    protected $table = 'crm_capital_owners';

    protected $fillable = ['workspace_id', 'name', 'share_percent', 'notes', 'is_active', 'created_by'];

    protected $casts = ['share_percent' => 'decimal:2', 'is_active' => 'boolean'];

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

    public function entries() { return $this->hasMany(CrmCapitalEntry::class, 'owner_id')->orderBy('entry_date')->orderBy('id'); }

    public function capitalTotal(): float
    {
        return round((float) $this->entries->where('type', 'capital')->sum('amount'), 2);
    }

    public function drawingsTotal(): float
    {
        return round((float) $this->entries->where('type', 'drawing')->sum('amount'), 2);
    }

    /** What the business owes this owner: capital put in minus drawings taken out. */
    public function balance(): float
    {
        return round($this->capitalTotal() - $this->drawingsTotal(), 2);
    }

    public function formData(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'share_percent' => $this->share_percent !== null ? (float) $this->share_percent : null,
            'notes' => $this->notes, 'is_active' => (bool) $this->is_active];
    }
}
