<?php

namespace App;

use App\Scopes\ScopesCrmWorkspaceThroughRelation;
use Illuminate\Database\Eloquent\Model;

class CrmMailFolder extends Model
{
    use ScopesCrmWorkspaceThroughRelation;

    protected $table = 'crm_mail_folders';

    public const TYPES = ['inbox', 'sent', 'drafts', 'archive', 'junk', 'trash', 'custom'];

    protected $fillable = [
        'account_id', 'name', 'path', 'type',
        'uidvalidity', 'last_uid', 'message_count', 'unread_count',
    ];

    protected $casts = [
        'uidvalidity'   => 'integer',
        'last_uid'      => 'integer',
        'message_count' => 'integer',
        'unread_count'  => 'integer',
    ];

    protected static function crmWorkspaceRelation(): string
    {
        return 'account';
    }

    public function account()
    {
        return $this->belongsTo(CrmMailAccount::class, 'account_id');
    }

    public function messages()
    {
        return $this->hasMany(CrmMailMessage::class, 'folder_id');
    }
}
