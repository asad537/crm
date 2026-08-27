<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Dieline extends Model
{
    protected $table = 'dielines';

    protected $fillable = [
        'project_id', 'file_name', 'file_path', 'file_size', 'status', 'is_company_upload', 'change_request_comment'
    ];

    public function project()
    {
        return $this->belongsTo(CustomProject::class, 'project_id');
    }

    public function mockups()
    {
        return $this->hasMany(Mockup::class, 'dieline_id');
    }
}
