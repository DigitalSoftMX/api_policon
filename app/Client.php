<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = ['user_id', 'birthdate', 'sex', 'address', 'current_balance', 'shared_balance', 'points', 'ids', 'active'];
    // Relacion con el usuario 
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    // Relacion con los escaneos QR's
    public function qrs()
    {
        return $this->hasMany(SalesQr::class);
    }
    // Relacion con los puntos de cada estacion
    public function puntos()
    {
        return $this->hasMany(Point::class);
    }
}
