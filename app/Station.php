<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Station extends Model
{
    /* Accediendo a la tabla station */
    protected $table = 'station';
    protected $fillable = ['name', 'address', 'phone', 'email', 'number_station', 'image', 'winner', 'active'];
    // Relacion con las ventas por QR
    public function qrs()
    {
        return $this->hasMany(SalesQr::class);
    }
}
