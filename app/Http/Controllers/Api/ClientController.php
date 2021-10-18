<?php

namespace App\Http\Controllers\Api;

use App\ExcelSales;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\Validation;
use App\Station;
use App\SalesQr;
use DateTime;
use Exception;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    private $user, $client, $points, $validate;
    public function __construct(Validation $validation)
    {
        $this->validate = $validation;
        $this->user = auth()->user();
        if ($this->user == null || $this->user->roles->first()->id != 5) {
            $this->logout(JWTAuth::getToken());
        } else {
            $this->client = $this->user->client;
            $this->points = $this->client->paymentsQrs->where('active', 1)->sortByDesc('created_at');
        }
    }
    // funcion para obtener informacion del usuario hacia la pagina princial
    public function index()
    {
        $data['id'] = $this->user->id;
        $data['name'] = "{$this->user->name} {$this->user->first_surname} {$this->user->second_surname}";
        $data['membership'] = $this->user->membership;
        $data['stations'] = [];
        foreach (Station::where('active', 1)->get() as $station) {
            $pointsPerStation = $this->points->where('station_id', $station->id)->where('status_id', 2)->sum('points');
            if ($pointsPerStation > 0) {
                array_push($data['stations'], array('id' => $station->id, 'station' => "$station->name", 'points' => $pointsPerStation));
            }
        }
        return $this->validate->successResponse('message', $data);
    }
    // Historial de puntos por estacion
    public function dates(Station $station)
    {
        if ($station->start) {
            if ($station->end)
                return array('start' => $station->start, 'end' => $station->end);
            $end = new DateTime($station->start);
            $end->modify('last day of this month');
            $end = $end->format('Y-m-d');
            return array('start' => $station->start, 'end' => $end);
        }
        $end = new DateTime(date('Y-m') . '-01');
        $end->modify('last day of this month');
        $end = $end->format('Y-m-d');
        return array('start' => date('Y-m') . '-01', 'end' => $end);
        /* $points = $this->points->where('station_id', $station->id)->sortByDesc('created_at');
        $dates = $points->map(function ($model) {
            return $model->created_at->format('Y-m-d');
        })->toArray();
        $dates = array_values($dates);
        $dates = array_unique($dates);
        return $dates; */
    }
    // Historial de puntos por estación
    public function pointsStation(Request $request, Station $station)
    {
        $validator = Validator::make($request->only('date'), ['date' => 'required|date_format:Y-m-d']);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), null);
        }
        $points = [];
        foreach (SalesQr::where([['client_id', $this->client->id], ['station_id', $station->id], ['active', 1]])->whereDate('created_at', $request->date)->orderBy('created_at', 'desc')->get() as $point) {
            if ($point->status_id != 2)
                $data['id'] = $point->id;
            $data['sale'] = $point->sale;
            $data['product'] = $point->gasoline;
            $data['liters'] = "{$point->liters} litros";
            $data['hour'] = $point->created_at->format('H:i');
            $data['points'] = "{$point->points} puntos";
            $data['status'] = $point->status->name;
            array_push($points, $data);
            $data = [];
        }
        return count($points) > 0 ? $this->successResponse('points', $points) : $this->errorResponse('Aun no tienes tickets registrados');
    }
    // Suma de puntos por el cliente
    public function addPoints(Request $request)
    {
        return $this->registerOrUpdateQr($request);
    }
    // Funcion para actulizar la venta por ingreso incorrecto
    public function updateSale(Request $request, SalesQr $qr)
    {
        if ($qr->status_id == 2)
            return $this->errorResponse('Esta venta no se puede editar');
        return $this->registerOrUpdateQr($request, $qr);
    }
    // Registro y actualizacion de las qr's escaneados
    private function registerOrUpdateQr(Request $request, SalesQr $qr = null)
    {
        $validate = $this->validate->validateSale($request);
        if (!is_bool($validate))
            return $validate;
        $station = Station::where('number_station', $request->station)->first();
        $request->merge([
            'client_id' => $this->client->id, 'station_id' => $station->id,
            'sale' => $request->ticket, 'product' => strtoupper($request->product),
            'created_at' => $request->date
        ]);
        if ($qr) {
            $qr->update($request->except(['status_id', 'active']));
        } else {
            if (SalesQr::where([['station_id', $station->id], ['sale', $request->ticket]])->exists())
                return $this->errorResponse('El ticket ya ha sido registrado');
            $qr = SalesQr::create($request->all());
        }
        if (ExcelSales::where([
            ['station_id', $station->id], ['ticket', $request->ticket], ['date', $request->date],
            ['product', 'like', "{$request->product}%"], ['liters', $request->liters], ['payment', $request->payment],
            ['payment_type', $request->payment_type]
        ])->exists()) {
            $count = SalesQr::where([['client_id', $this->client->id], ['active', 1], ['status_id', 2], ['created_at', 'like', $qr->created_at->format('Y-m-d') . '%']])->count();
            switch ($request->product) {
                case str_contains($request->product, 'EXTRA'):
                    $points = $this->getPoints($request->liters, 1.5, $count);
                    break;
                case str_contains($request->product, 'SUPREME'):
                    $points = $this->getPoints($request->liters, 2, $count);
                    break;
                case str_contains($request->product, 'DIESEL'):
                    $points = $this->getPoints($request->liters, 1, $count);
                    break;
            }
            $qr->update(['points' => $points, 'status_id' => 2]);
            $this->client->points += $points;
            $this->client->save();
            return $this->successResponse('message', 'Se han sumado sus puntos');
        }
        return $this->successResponse('message', 'Su ticket ha sido registrado, se notificará en el momento que sea validado');
    }
    // Funcion principal para la ventana de abonos a las estaciones o ver los canjes
    /* public function getListStations()
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
    } */
    // Funcion para devolver el historial de abonos a la cuenta del usuario
    /* public function history(Request $request)
    {
        try {
            $payments = array();
            switch ($request->type) {
                    case 'payment':
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
                        break;
                    case 'balance':
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
                        break;
                    case 'share':
                        if (count($balances = $this->getBalances(new SharedBalance(), $request->start, $request->end, $user, 4, 'transmitter_id')) > 0) {
                            $payments = $this->getSharedBalances($balances, 'receiver');
                            return $this->successResponse('balances', $payments, null, null);
                        }
                        break;
                    case 'received':
                        if (count($balances = $this->getBalances(new SharedBalance(), $request->start, $request->end, $user, 4, 'receiver_id')) > 0) {
                            $payments = $this->getSharedBalances($balances, 'transmitter');
                            return $this->successResponse('balances', $payments, null, null);
                        }
                        break;
                    case 'exchange':
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
                        break;
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
    } */
    // Funcion para devolver el arreglo de historiales
    /* private function getBalances($model, $start, $end, $status = null, $type = null)
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
    } */
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
    // Calcular numero de puntos
    private function getPoints($liters, $sum, $count)
    {
        $val = $liters;
        $liters = explode(".", $val);
        if (count($liters) > 1) {
            $points = $liters[0] . '.' . $liters[1][0];
            $points = round($points, 0, PHP_ROUND_HALF_DOWN);
        } else {
            $points = intval($val);
        }
        return $points * ($sum + ($count * 0.25));
    }
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
