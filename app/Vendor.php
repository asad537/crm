<?php
namespace App;
use Illuminate\Database\Eloquent\Model;
class Vendor extends Model
{
    protected $fillable = ['workspace_id','name','category','vendor_type','trn_number','phone','email','address','notes'];

    /** Vendor types drive which purchase columns/fields are shown. */
    public const TYPES = [
        'paper'      => 'Paper',
        'ctp_plate'  => 'CTP Plate',
        'die_making' => 'Die Making',
        'general'    => 'General',
    ];

    public function typeLabel(): string
    {
        return self::TYPES[$this->vendor_type] ?? 'General';
    }
    protected static function boot(){ parent::boot(); static::addGlobalScope('workspace', function($q){ if($id=\App\Support\CrmWorkspaceContext::id()) $q->where('workspace_id',$id); }); static::creating(function($v){ if(!$v->workspace_id && ($id=\App\Support\CrmWorkspaceContext::id())) $v->workspace_id=$id; }); }
    public function purchases(){ return $this->hasMany(VendorPurchase::class,'vendor_id'); }
}
