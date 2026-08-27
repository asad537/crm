<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CrmMessage extends Model
{
    use \App\Scopes\ScopesCrmWorkspaceThroughRelation;
    protected static function crmWorkspaceRelation() { return 'inquiry'; }
    protected $table = 'crm_messages';

    protected $fillable = [
        'crm_email_id',
        'sender_type',
        'crm_user_id',
        'message_body',
        'message_id',
        'attachments',
        'is_read'
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_read' => 'boolean'
    ];

    public function inquiry()
    {
        return $this->belongsTo(CrmEmail::class, 'crm_email_id');
    }

    public function user()
    {
        return $this->belongsTo(CrmUser::class, 'crm_user_id');
    }
}
