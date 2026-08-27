<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserCredit extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_credits';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'firebase_uid',
        'credits',
        'free_credit_granted',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'free_credit_granted' => 'boolean',
        'credits' => 'integer',
    ];
}
