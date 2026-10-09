<?php

namespace App;

use App\Scopes\ScopesCrmWorkspaceThroughRelation;
use Illuminate\Database\Eloquent\Model;

class CrmMailThread extends Model
{
    use ScopesCrmWorkspaceThroughRelation;

    protected $table = 'crm_mail_threads';

    protected $fillable = [
        'account_id', 'subject_norm', 'root_message_id',
        'last_message_at', 'message_count', 'unread_count',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'message_count'   => 'integer',
        'unread_count'    => 'integer',
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
        return $this->hasMany(CrmMailMessage::class, 'thread_id')->orderBy('received_at');
    }
}
