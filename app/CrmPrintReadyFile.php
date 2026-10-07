<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CrmPrintReadyFile extends Model
{
    protected $table = 'crm_print_ready_files';
    protected $fillable = ['ticket_id', 'path', 'name', 'size', 'uploaded_by'];

    public function ticket() { return $this->belongsTo(CrmPrintReadyTicket::class, 'ticket_id'); }
    public function uploader() { return $this->belongsTo(CrmUser::class, 'uploaded_by'); }
}
