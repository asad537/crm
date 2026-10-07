<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CrmPrintReadyNote extends Model
{
    protected $table = 'crm_print_ready_notes';
    protected $fillable = ['ticket_id', 'user_id', 'type', 'body'];

    public function ticket() { return $this->belongsTo(CrmPrintReadyTicket::class, 'ticket_id'); }
    public function user() { return $this->belongsTo(CrmUser::class, 'user_id'); }
}
