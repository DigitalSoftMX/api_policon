<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalesQr extends Model
{
    protected $fillable = ['sale', 'gasoline_id', 'liters', 'points', 'payment', 'station_id', 'client_id'];
    // Relacion con la estacion
    public function station()
    {
        return $this->belongsTo(Station::class);
    }
}
