<?php

namespace App\Http\Controllers\Api;

use App\Client;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\User;
use Carbon\Carbon;
use Exception;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    // Metodo para inicar sesion
    public function login(Request $request)
    {
        if (($user = User::where('membership', $request->email)->first()) != null) {
            $request->merge(['email' => $user->email]);
        } else {
            $user = User::where('email', $request->email)->first();
        }
        if (!$user)
            return $this->errorResponse('Lo sentimos, el usuario no esta registrado', null);
        if ($user->roles->first()->id == 4 || $user->roles->first()->id == 5) {
            $validator = Validator::make($request->only('email'), ['email' => 'email']);
            return ($validator->fails()) ?
                $this->errorResponse($validator->errors(), null) :
                $this->getToken($request, $user);
        }
        return $this->errorResponse('Usuario no autorizado', null);
    }
    // Metodo para registrar a un usuario nuevo
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'first_surname' => 'required|string',
            'email' => [
                'required', 'email', Rule::unique((new User)->getTable())
            ],
            'phone' => request('phone') != '' ? ['min:10', Rule::unique((new User)->getTable())] : '',
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), null);
        }
        // Membresia aleatoria no repetible
        while (true) {
            $membership = 'P' . substr(Carbon::now()->format('Y'), 2) . rand(100000, 999999);
            if (!(User::where('membership', $membership)->exists())) {
                $request->merge(['membership' => $membership]);
                break;
            }
        }
        $password = $request->password;
        $user = User::create($request->merge(['password' => bcrypt($request->password)])->all());
        Client::create($request->merge(['user_id' => $user->id])->all());
        $user->roles()->sync(5);
        $request->merge(['password' => $password]);
        return $this->getToken($request, $user);
    }
    // Metodo para cerrar sesion
    public function logout(Request $request)
    {
        try {
            JWTAuth::invalidate(JWTAuth::parseToken($request->token));
            return $this->successReponse('message', 'Cierre de sesión correcto');
        } catch (Exception $e) {
            return $this->errorResponse('Token inválido', null);
        }
    }
    // Metodo para iniciar sesion, delvuelve el token
    private function getToken($request, $user)
    {
        if (!$token = JWTAuth::attempt($request->only('email', 'password')))
            return $this->errorResponse('Datos incorrectos', null);
        $user->update(['remember_token' => $token]);
        if ($user->roles->first()->id == 5)
            $user->client->update($request->only('ids'));
        return $this->successReponse('token', $token);
    }
    // Funcion mensaje correcto
    private function successReponse($name, $data)
    {
        return response()->json([
            'ok' => true,
            $name => $data
        ]);
    }
    // Metodo mensaje de error
    private function errorResponse($message, $email)
    {
        return response()->json([
            'ok' => false,
            'message' => $message,
            'id' => $email
        ]);
    }
}
