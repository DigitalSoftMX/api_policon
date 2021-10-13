<?php

namespace App\Http\Controllers\Api;

use App\DataCar;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        switch (($user = Auth::user())->roles[0]->name) {
            case 'usuario':
                $data = $this->getDataUser($user);
                $data['email'] = $user->email;
                return $this->successResponse('user', $data);
            case 'despachador':
                return $this->successResponse('user', $this->getDataUser($user));
            default:
                return $this->logout(JWTAuth::getToken());
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        switch (($user = Auth::user())->roles[0]->name) {
            case 'usuario':
                $validator = Validator::make($request->all(), [
                    'name' => 'required|string',
                    'first_surname' => 'required|string',
                    'email' => [
                        'required', 'email', Rule::unique((new User)->getTable())->ignore($user->id ?? null)
                    ],
                    'password' => request('password') != '' ? 'string|min:6' : '',
                    'phone' => request('phone') != '' ? ['min:10', Rule::unique((new User)->getTable())->ignore($user->id ?? null)] : '',
                ]);
                if ($validator->fails()) {
                    return $this->errorResponse($validator->errors());
                }
                // Registrando la informacion basica del cliente
                $user->update($request->only('name', 'first_surname', 'second_surname', 'email', 'phone'));
                // Registrando la contraseña
                if ($request->password != "") {
                    $user->update(['password' => bcrypt($request->password)]);
                    $this->logout(JWTAuth::getToken());
                    return $this->successResponse('message', 'Datos actualizados correctamente, inicie sesión nuevamente');
                }
                break;
            case 'despachador':
                $validator = Validator::make($request->all(), [
                    'name' => 'required|string',
                    'first_surname' => 'required|string',
                    'phone' => request('phone') != '' ? ['min:10', Rule::unique((new User)->getTable())->ignore($user->id ?? null)] : '',
                ]);
                if ($validator->fails()) {
                    return $this->errorResponse($validator->errors());
                }
                $user->update($request->only('name', 'first_surname', 'second_surname', 'phone'));
                break;
            default:
                return $this->logout(JWTAuth::getToken());
        }
        return $this->successResponse('message', 'Datos actualizados correctamente');
    }

    // Funcion para obtener la informacion basica de un usuario
    private function getDataUser($user)
    {
        $data = array(
            'id' => $user->id,
            'name' => $user->name,
            'first_surname' => $user->first_surname,
            'second_surname' => $user->second_surname,
            'phone' => $user->phone,
        );
        return $data;
    }
    // Metodo para cerrar sesion
    private function logout($token)
    {
        try {
            JWTAuth::invalidate(JWTAuth::parseToken($token));
            return $this->successResponse('message', 'Usuario no autorizado');
        } catch (Exception $e) {
            return $this->errorResponse('Token invalido');
        }
    }
    // Funcion mensaje correcto
    private function successResponse($name, $data)
    {
        return response()->json([
            'ok' => true,
            $name => $data
        ]);
    }
    // Funcion mensaje de error
    private function errorResponse($message)
    {
        return response()->json([
            'ok' => false,
            'message' => $message
        ]);
    }
}
