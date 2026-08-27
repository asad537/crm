<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Author extends Model
{
    protected $fillable = [
        'id', 'name', 'slug', 'email', 'bio', 'image', 
        'facebook', 'twitter', 'linkedin', 'instagram', 'website', 
        'status'
    ];

    /**
     * Get the blogs for the author.
     */
    public function blogs()
    {
        return $this->hasMany(Blog::class);
    }
}
