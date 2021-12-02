<?php

namespace App\Repositories;

use App\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class Validation extends ResponsesAndLogout
{
    // Validando los datos del cliente
    public function validateUser(Request $request, $user = null)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'first_surname' => 'required|string',
            'second_surname' => 'required|string',
            'birthdate' => $request->birthdate ? 'date_format:Y-m-d' : '',
            'email' => ['required', 'email', Rule::unique((new User)->getTable())->ignore($user->id ?? null)],
            'phone' => $request->phone ? ['min:10', Rule::unique((new User)->getTable())->ignore($user->id ?? null)] : '',
            'password' => $user ? '' : 'required|string|min:6',
            'address' => $request->address ? 'string' : '',
        ]);
        return $validator->fails() ? $this->errorResponse($validator->errors()) : true;
    }
    // Validanto los datos del QR
    public function validateSale(Request $request, $qr = null)
    {
        $validator = Validator::make($request->all(), [
            'station' => ['required', 'integer', 'station' => 'exists:App\Station,number_station'],
            'date' => 'required|date_format:Y-m-d H:i:s', 'ticket' => 'required|string',
            'product' => ['required', 'string', 'not_regex:/diesel|D(?:I[E\xC9]SEL|i[e\xE9]sel)/'],
            'liters' => 'required|numeric|min:1', 'payment' => 'required|numeric|min:500',
            'photo' => $qr ? '' : 'required|image',
        ]);
        return $validator->fails() ? $this->errorResponse($validator->errors()) : true;
    }
}
