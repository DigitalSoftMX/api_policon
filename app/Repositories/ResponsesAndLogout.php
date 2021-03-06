<?php

namespace App\Repositories;

use Exception;
use Tymon\JWTAuth\Facades\JWTAuth;

class ResponsesAndLogout
{
    // Metodo para cerrar sesion
    public function logout($token, $session = false)
    {
        try {
            JWTAuth::invalidate(JWTAuth::parseToken($token));
            return $session ? $this->successResponse('message', 'Cierre de sesión correcto') :
                $this->errorResponse('Token invalido');
        } catch (Exception $e) {
            return $this->errorResponse('Token invalido');
        }
    }
    // Funcion mensajes de error
    public function errorResponse($message)
    {
        return response()->json(['ok' => false, 'message' => $message]);
    }
    // Funcion mensaje correcto
    public function successResponse($name, $data)
    {
        return response()->json(['ok' => true, $name => $data]);
    }
}
