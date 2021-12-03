<?php

namespace App;

use App\Api\Status;
use Illuminate\Database\Eloquent\Model;

class SalesQr extends Model
{
    protected $fillable = ['client_id', 'station_id', 'sale', 'product', 'liters', 'points', 'payment', 'photo', 'status_id', 'created_at'];
    // Relacion con la estacion
    public function station()
    {
        return $this->belongsTo(Station::class);
    }
    // Relacion con los status
    public function status()
    {
        return $this->belongsTo(Status::class);
    }
}
