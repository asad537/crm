<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/** A file attached to a customer sale (stored on the private "local" disk, opened through the CRM). */
class CustomerSaleAttachment extends Model
{
    protected $fillable = ['workspace_id', 'customer_sale_id', 'path', 'original_name', 'mime', 'size', 'uploaded_by'];

    public function sale()
    {
        return $this->belongsTo(CustomerSale::class, 'customer_sale_id');
    }

    public function uploader()
    {
        return $this->belongsTo(CrmUser::class, 'uploaded_by');
    }

    /** "245 KB", "1.2 MB". */
    public function sizeLabel(): string
    {
        $bytes = (int) $this->size;
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';

        return max(1, (int) round($bytes / 1024)) . ' KB';
    }
}
