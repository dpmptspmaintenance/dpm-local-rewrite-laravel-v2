<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessPage extends Model
{
    protected $fillable = ['name', 'description', 'slug', 'image'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'access_page_user');
    }
}
