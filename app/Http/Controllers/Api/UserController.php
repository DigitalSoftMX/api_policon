<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\ResponsesAndLogout;
use App\Repositories\Validation;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    private $user, $client, $response, $validate;
    public function __construct(ResponsesAndLogout $response, Validation $validate)
    {
        $this->user = auth()->user();
        $this->response = $response;
        $this->validate = $validate;
        if ($this->user and $this->user->roles->first()->id == 5) {
            $this->client = $this->user->client;
        } else {
            $this->response->logout(JWTAuth::getToken());
        }
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = $this->getDataUser($this->user);
        $data['birthdate'] = $this->client->birthdate;
        $data['sex'] = $this->client->sex;
        $data['email'] = $this->user->email;
        $data['address'] = $this->client->address;
        return $this->response->successResponse('user', $data);
        /* switch (($user = Auth::user())->roles[0]->name) {
            case 'usuario':
                $data = $this->getDataUser($user);
                $data['email'] = $user->email;
                return $this->successResponse('user', $data);
            case 'despachador':
                return $this->successResponse('user', $this->getDataUser($user));
            default:
                return $this->logout(JWTAuth::getToken());
        } */
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
        $validator = $this->validate->validateUser($request, $this->user);
        if (!is_bool($validator))
            return $validator;
        $this->user->update($request->only(['name', 'first_surname', 'second_surname', 'email', 'phone']));
        if ($request->password)
            $this->user->update(['password' => bcrypt($request->password)]);
        $this->client->update($request->only(['birthdate', 'sex', 'address']));
        return $this->response->successResponse('message', 'Datos actualizados correctamente');
        /* switch (($user = Auth::user())->roles[0]->name) {
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
        }*/
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
}
