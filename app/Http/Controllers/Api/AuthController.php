<?php

namespace App\Http\Controllers\Api;

use App\Client;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\ResponsesAndLogout;
use App\Repositories\Validation;
use Illuminate\Support\Facades\Validator;
use App\User;
use Carbon\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    private $response, $validation;
    public function __construct(ResponsesAndLogout $response, Validation $validation)
    {
        $this->response = $response;
        $this->validation = $validation;
    }
    // Metodo para inicar sesion
    public function login(Request $request)
    {
        if ($user = User::where('membership', $request->email)->first()) {
            $request->merge(['email' => $user->email]);
        } else {
            $user = User::where('email', $request->email)->first();
        }
        if (!$user)
            return $this->response->errorResponse('Lo sentimos, el usuario no esta registrado');
        if ($user->roles->first()->id == 5) {
            $validator = Validator::make($request->only(['email', 'password']), ['email' => 'required|email', 'password' => 'required']);
            return ($validator->fails()) ?
                $this->response->errorResponse($validator->errors()) :
                $this->getToken($request, $user);
        }
        return $this->response->errorResponse('Usuario no autorizado');
    }
    // Metodo para registrar a un usuario nuevo
    public function register(Request $request)
    {
        $validator = $this->validation->validateUser($request);
        if (!is_bool($validator))
            return $validator;
        // Membresia aleatoria no repetible
        while (true) {
            $membership = 'MO' . substr(Carbon::now()->format('Y'), 2) . rand(100000, 999999);
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
        return $this->response->logout($request->token, true);
    }
    // Metodo para iniciar sesion, delvuelve el token
    private function getToken($request, $user)
    {
        if (!$token = JWTAuth::attempt($request->only('email', 'password')))
            return $this->response->errorResponse('Verifique que su contraseña e email sean correctos');
        $user->update(['remember_token' => $token]);
        if ($user->roles->first()->id == 5)
            $user->client->update($request->only('ids'));
        return $this->response->successResponse('token', $token);
    }
}
