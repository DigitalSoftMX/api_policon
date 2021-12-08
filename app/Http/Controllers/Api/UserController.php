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

        $this->user and $this->user->roles->first()->id == 5 ?
            $this->client = $this->user->client :
            $this->response->logout(JWTAuth::getToken());
    }

    public function edit()
    {
        $data['id'] = $this->user->id;
        $data['name'] = $this->user->name;
        $data['first_surname'] = $this->user->first_surname;
        $data['second_surname'] = $this->user->second_surname;
        $data['phone'] = $this->user->phone;
        $data['birthdate'] = $this->client->birthdate;
        $data['sex'] = $this->client->sex;
        $data['email'] = $this->user->email;
        $data['address'] = $this->client->address;
        return $this->response->successResponse('user', $data);
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
    }
}
