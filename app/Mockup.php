<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Mockup extends Model
{
    protected $table = 'mockups';

    protected $fillable = [
        'dieline_id', 'file_name', 'file_path', 'file_size',
        'status', 'is_company', 'change_request_comment'
    ];

    public function dieline()
    {
        return $this->belongsTo(Dieline::class, 'dieline_id');
    }
}
