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
    // Relacion para los depositos realizados por el cliente
    public function deposits()
    {
        return $this->hasMany(Deposit::class);
    }
    // Relacion con los depositos compartidos
    public function depositReceived()
    {
        return $this->hasMany(SharedBalance::class, 'receiver_id', 'id');
    }
    // Relacion para los depositos realizados por el cliente
    public function historyDeposits()
    {
        return $this->belongsTo(Deposit::class);
    }
    // Relacion para los contactos del cliente
    public function contacts()
    {
        return $this->hasMany(Contact::class, 'transmitter_id', 'id');
    }
    // Relacion con los pagos que ha realizado
    public function payments()
    {
        return $this->hasMany(Sale::class);
    }
    // Relacion con los escaneos QR's
    public function paymentsQrs()
    {
        return $this->hasMany(SalesQr::class);
    }
    // Relacion con los canjes
    public function exchanges()
    {
        return $this->hasMany(Exchange::class);
    }
    // Relacion con los usuarioa a referencia
    public function main()
    {
        return $this->belongsToMany(User::class, 'user_client');
    }
}
