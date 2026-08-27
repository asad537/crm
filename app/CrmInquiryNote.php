<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CrmInquiryNote extends Model
{
    protected $table = 'crm_inquiry_notes';

    protected $fillable = [
        'crm_email_id', 'sender_id', 'sender_name', 'sender_role', 'body',
    ];

    public function email()
    {
        return $this->belongsTo(CrmEmail::class, 'crm_email_id');
    }

    public function sender()
    {
        return $this->belongsTo(CrmUser::class, 'sender_id');
    }
}
