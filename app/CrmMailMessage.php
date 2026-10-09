<?php

namespace App;

use App\Scopes\ScopesCrmWorkspaceThroughRelation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One raw email (inbound or outbound) in a connected mailbox.
 * Optionally linked to a CRM lead via crm_email_id.
 */
class CrmMailMessage extends Model
{
    use SoftDeletes, ScopesCrmWorkspaceThroughRelation;

    protected $table = 'crm_mail_messages';

    protected $fillable = [
        'account_id', 'folder_id', 'thread_id', 'crm_email_id',
        'uid', 'message_id', 'in_reply_to', 'references_header',
        'subject', 'from_name', 'from_email', 'to_json', 'cc_json', 'bcc_json', 'reply_to',
        'text_body', 'html_body', 'snippet', 'has_attachments',
        'is_read', 'is_starred', 'is_draft', 'is_outgoing',
        'received_at', 'sent_at', 'headers_json',
    ];

    protected $casts = [
        'uid'             => 'integer',
        'to_json'         => 'array',
        'cc_json'         => 'array',
        'bcc_json'        => 'array',
        'headers_json'    => 'array',
        'has_attachments' => 'boolean',
        'is_read'         => 'boolean',
        'is_starred'      => 'boolean',
        'is_draft'        => 'boolean',
        'is_outgoing'     => 'boolean',
        'received_at'     => 'datetime',
        'sent_at'         => 'datetime',
    ];

    protected static function crmWorkspaceRelation(): string
    {
        return 'account';
    }

    // ---- Relations -------------------------------------------------------

    public function account()
    {
        return $this->belongsTo(CrmMailAccount::class, 'account_id');
    }

    public function folder()
    {
        return $this->belongsTo(CrmMailFolder::class, 'folder_id');
    }

    public function thread()
    {
        return $this->belongsTo(CrmMailThread::class, 'thread_id');
    }

    /** The CRM lead/inquiry this message is linked to, if any. */
    public function lead()
    {
        return $this->belongsTo(CrmEmail::class, 'crm_email_id');
    }

    public function attachments()
    {
        return $this->hasMany(CrmMailAttachment::class, 'message_id');
    }

    // ---- Scopes ----------------------------------------------------------

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeStarred($query)
    {
        return $query->where('is_starred', true);
    }

    public function scopeInFolder($query, $folderId)
    {
        return $query->where('folder_id', $folderId);
    }

    // ---- Helpers ---------------------------------------------------------

    /** Normalise a subject for thread grouping (strips Re:/Fwd: prefixes). Never the only threading key. */
    public static function normalizeSubject(?string $subject): string
    {
        $s = trim((string) $subject);
        $s = preg_replace('/^\s*((re|fw|fwd|aw|wg)\s*:\s*)+/i', '', $s);
        return mb_strtolower(trim($s));
    }
}
