<?php

namespace App\Repositories;

use App\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class Validation extends ResponsesAndLogout
{
    public function validateUser(Request $request, $user = null)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'first_surname' => 'required|string',
            'second_surname' => 'required|string',
            'birthdate' => request('birthdate') != '' ? 'date_format:Y-m-d' : '',
            'email' => ['required', 'email', Rule::unique((new User)->getTable())->ignore($user->id ?? null)],
            'phone' => request('phone') != '' ? ['min:10', Rule::unique((new User)->getTable())->ignore($user->id ?? null)] : '',
            'password' => $user ? '' : 'required|string|min:6',
            'address' => request('address') != '' ? 'string' : '',
        ]);
        if ($validator->fails())
            return $this->errorResponse($validator->errors());
        return true;
    }
    public function validateSale(Request $request, $qr = null)
    {
        $validator = Validator::make($request->all(), [
            'station' => ['required', 'integer', 'station' => 'exists:App\Station,number_station'],
            'ticket' => 'required|string', 'product' => 'required|string',
            'payment' => 'required|numeric', 'date' => 'required|date_format:Y-m-d H:i:s',
            'liters' => 'required|numeric|min:25|exclude_if:liters,0',
            'photo' => $qr ? '' : 'required|image',
        ]);
        if ($validator->fails())
            return $this->errorResponse($validator->errors());
        return true;
    }
}
