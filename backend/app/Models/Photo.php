<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Photo extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'url'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'photo_users', 'photo_id', 'user_id');
    }
}
