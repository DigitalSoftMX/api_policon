<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Station;
use App\SalesQr;
use Exception;
use Tymon\JWTAuth\Facades\JWTAuth;

class ClientController extends Controller
{
    private $user, $client;
    public function __construct()
    {
        $this->user = auth()->user();
        if ($this->user == null || $this->user->roles->first()->id != 5) {
            $this->logout(JWTAuth::getToken());
        } else {
            $this->client = $this->user->client;
        }
    }
    // funcion para obtener informacion del usuario hacia la pagina princial
    public function index()
    {
        $data['id'] = $this->user->id;
        $data['name'] = $this->user->name;
        $data['first_surname'] = $this->user->first_surname;
        $data['second_surname'] = $this->user->second_surname;
        $data['email'] = $this->user->email;
        $data['client']['membership'] = $this->user->membership;
        $data['client']['current_balance'] = $this->client->deposits->where('status', 4)->sum('balance');
        $data['client']['shared_balance'] = $this->client->depositReceived->where('status', 4)->sum('balance');
        $data['client']['total_shared_balance'] = count($this->client->depositReceived->where('status', 4)->where('balance', '>', 0));
        $data['client']['points'] = $this->client->points;
        return $this->successResponse('user', $data, null, null);
    }
    // Funcion principal para la ventana de abonos a las estaciones o ver los canjes
    public function getListStations()
    {
        $stations = array();
        foreach (Station::all() as $station) {
            $dataStation['id'] = $station->id;
            $dataStation['name'] = $station->abrev . ' - ' . $station->name;
            $dataStation['number_station'] = $station->number_station;
            $dataStation['address'] = $station->address;
            $dataStation['email'] = $station->email;
            $dataStation['phone'] = $station->phone;
            $dataStation['image'] = asset($station->image);
            array_push($stations, $dataStation);
        }
        $exchanges = array();
        foreach ($this->client->exchanges->where('status', '!=', 14) as $exchange) {
            $dataExchange['station'] = $exchange->station->name;
            $dataExchange['invoice'] = $exchange->exchange;
            $dataExchange['status'] = $exchange->estado->name;
            $dataExchange['date'] = $exchange->created_at->format('Y/m/d');
            array_push($exchanges, $dataExchange);
        }
        return $this->successResponse('stations', $stations, 'exchanges', $exchanges);
    }
    // Funcion para devolver el historial de abonos a la cuenta del usuario
    public function history(Request $request)
    {
        try {
            $payments = array();
            switch ($request->type) {
                    /* case 'payment':
                        if (count($balances = $this->getBalances(new Sale(), $request->start, $request->end, $user, null, null)) > 0) {
                            foreach ($balances as $balance) {
                                $data['balance'] = $balance->payment;
                                $data['station'] = $balance->station->name;
                                $data['liters'] = $balance->liters;
                                $data['date'] = $balance->created_at->format('Y/m/d');
                                $data['hour'] = $balance->created_at->format('H:i:s');
                                $data['gasoline'] = $balance->gasoline->name;
                                $data['no_island'] = $balance->no_island;
                                $data['no_bomb'] = $balance->no_bomb;
                                $data['sale'] = $balance->sale;
                                array_push($payments, $data);
                            }
                            return $this->successResponse('payments', $payments, null, null);
                        }
                        break; */
                    /* case 'balance':
                        if (count($balances = $this->getBalances(new Deposit(), $request->start, $request->end, $user, 4, null)) > 0) {
                            foreach ($balances as $balance) {
                                $data['balance'] = $balance->balance;
                                $data['station'] = $balance->station->name;
                                $data['status'] = $balance->deposit->name;
                                $data['date'] = $balance->created_at->format('Y/m/d');
                                $data['hour'] = $balance->created_at->format('H:i:s');
                                array_push($payments, $data);
                            }
                            return $this->successResponse('balances', $payments, null, null);
                        }
                        break; */
                    /* case 'share':
                        if (count($balances = $this->getBalances(new SharedBalance(), $request->start, $request->end, $user, 4, 'transmitter_id')) > 0) {
                            $payments = $this->getSharedBalances($balances, 'receiver');
                            return $this->successResponse('balances', $payments, null, null);
                        }
                        break; */
                    /* case 'received':
                        if (count($balances = $this->getBalances(new SharedBalance(), $request->start, $request->end, $user, 4, 'receiver_id')) > 0) {
                            $payments = $this->getSharedBalances($balances, 'transmitter');
                            return $this->successResponse('balances', $payments, null, null);
                        }
                        break; */
                    /* case 'exchange':
                        if (count($balances = $this->getBalances(new Exchange(), $request->start, $request->end, $user, 14, 'exchange')) > 0) {
                            foreach ($balances as $balance) {
                                $data['points'] = $balance->points;
                                $data['station'] = $balance->station->name;
                                $data['invoice'] = $balance->exchange;
                                $data['status'] = $balance->estado->name;
                                $data['status_id'] = $balance->status;
                                $data['date'] = $balance->created_at ? $balance->created_at->format('Y/m/d') : '';
                                array_push($payments, $data);
                            }
                            return $this->successResponse('exchanges', $payments, null, null);
                        }
                        break; */
                case 'points':
                    if (count($balances = $this->getBalances(new SalesQr(), $request->start, $request->end)) > 0) {
                        foreach ($balances as $balance) {
                            $data['points'] = $balance->points;
                            $data['station'] = $balance->station->name;
                            $data['status'] = 'Puntos sumados';
                            $data['sale'] = $balance->sale;
                            $data['date'] = $balance->created_at->format('Y/m/d');
                            array_push($payments, $data);
                        }
                        return $this->successResponse('points', $payments);
                    }
                    break;
            }
            return $this->errorResponse('Sin movimientos en la cuenta');
        } catch (Exception $e) {
            return $this->errorResponse('Error de consulta por fecha');
        }
    }
    // Funcion para devolver el arreglo de historiales
    private function getBalances($model, $start, $end, $status = null, $type = null)
    {
        $query = [['client_id', $this->client->id]];
        if ($type)
            $query = [[$type, $this->client->client->id]];
        if ($status)
            $query[1] = ['status', '!=', $status];
        if ($type == 'exchange')
            $query = [['client_id', $this->client->client->id]];
        if ($start == "" && $end == "") {
            $balances = $model::where($query)->get();
        } elseif ($start == "") {
            $balances = $model::where($query)->whereDate('created_at', '<=', $end)->get();
        } elseif ($end == "") {
            $balances = $model::where($query)->whereDate('created_at', '>=', $start)->get();
        } else {
            $balances = ($start > $end) ? null : $model::where($query)->whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end)->get();
        }
        return ($balances != null) ? $balances->sortByDesc('created_at') : null;
    }
    // Obteniendo el historial enviodo o recibido
    /* private function getSharedBalances($balances, $person)
    {
        $payments = array();
        foreach ($balances as $balance) {
            $payment['station'] = $balance->station->name;
            $payment['balance'] = $balance->balance;
            $payment['membership'] = $balance->$person->user->username;
            $payment['name'] = $balance->$person->user->name . ' ' . $balance->$person->user->first_surname . ' ' . $balance->$person->user->second_surname;
            $payment['date'] = $balance->created_at->format('Y/m/d');
            array_push($payments, $payment);
        }
        return $payments;
    } */
    // Metodo para cerrar sesion
    private function logout($token)
    {
        try {
            JWTAuth::invalidate(JWTAuth::parseToken($token));
            return $this->errorResponse('Token invalido');
        } catch (Exception $e) {
            return $this->errorResponse('Token invalido');
        }
    }
    // Funcion mensajes de error
    private function errorResponse($message)
    {
        return response()->json([
            'ok' => false,
            'message' => $message
        ]);
    }
    // Funcion mensaje correcto
    private function successResponse($name, $data, $array = null, $dataArray = null)
    {
        return ($array) ?
            response()->json([
                'ok' => true,
                $name => $data,
                $array => $dataArray
            ]) :
            response()->json([
                'ok' => true,
                $name => $data,
            ]);
    }
}
