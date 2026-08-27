<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    protected $table = 'email_logs';
    
    protected $fillable = [
        'to_email',
        'from_email',
        'subject',
        'body',
        'reply_to',
        'status',
        'error_message',
        'form_type',
        'ip_address',
        'user_agent',
    ];
    
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
