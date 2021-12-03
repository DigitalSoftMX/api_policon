<?php

namespace App;

use App\Notifications\ResetPasswordNotification;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

/* La clase implementa un Interface de JWTSubject */

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;
    // Metodo para validar un rol permitido
    public function verifyRole($role)
    {
        foreach ($this->roles as $rol) {
            if ($rol->id == $role) {
                return true;
            }
        }
        return false;
    }
    // Relacion a muchos para el rol del usuario
    public function roles()
    {
        return $this->belongsToMany('App\Role');
    }
    // Relacion usuario cliente
    public function client()
    {
        return $this->hasOne(Client::class);
    }
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'membership', 'first_surname', 'second_surname', 'email', 'phone', 'active', 'password',
        'remember_token'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'created_at', 'updated_at'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /* Metodos override de JWTSubject */

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return ['role' => $this->roles[0]->name];
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
