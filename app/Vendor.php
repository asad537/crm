<?php
namespace App;
use Illuminate\Database\Eloquent\Model;
class Vendor extends Model
{
    protected $fillable = ['workspace_id','name','category','trn_number','phone','email','address','notes'];
    protected static function boot(){ parent::boot(); static::addGlobalScope('workspace', function($q){ if($id=\App\Support\CrmWorkspaceContext::id()) $q->where('workspace_id',$id); }); static::creating(function($v){ if(!$v->workspace_id && ($id=\App\Support\CrmWorkspaceContext::id())) $v->workspace_id=$id; }); }
    public function purchases(){ return $this->hasMany(VendorPurchase::class,'vendor_id'); }
}
