<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['id', 'name', 'description', 'display_name'];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
    protected $guarded = ['id'];
}
