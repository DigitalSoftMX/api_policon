<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class Validation extends ResponsesAndLogout
{
    public function validateSale(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'station' => ['required', 'integer', 'station' => 'exists:App\Station,number_station'],
                'ticket' => 'required|string', 'payment_type' => 'required|string',
                'payment' => 'required|numeric|min:500|exclude_if:payment,0', 'product' => 'required|string',
                'liters' => 'required|numeric', 'date' => 'required|date_format:Y-m-d H:i'
            ]
        );
        if ($validator->fails())
            return $this->errorResponse($validator->errors(), null);
        return true;
    }
}
