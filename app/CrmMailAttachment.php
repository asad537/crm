<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/** Attachment metadata; files live on a private disk (never under the web root). */
class CrmMailAttachment extends Model
{
    protected $table = 'crm_mail_attachments';

    protected $fillable = [
        'message_id', 'original_name', 'mime_type', 'size',
        'disk', 'path', 'content_id', 'is_inline',
    ];

    protected $casts = [
        'size'      => 'integer',
        'is_inline' => 'boolean',
    ];

    public function message()
    {
        return $this->belongsTo(CrmMailMessage::class, 'message_id');
    }
}
