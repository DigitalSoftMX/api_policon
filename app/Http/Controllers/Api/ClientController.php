<?php

namespace App\Http\Controllers\Api;

use App\ExcelSales;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Period;
use App\Point;
use App\Repositories\Validation;
use App\Station;
use App\SalesQr;
use DateTime;
use Illuminate\Support\Facades\File;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Str;

class ClientController extends Controller
{
    private $user, $client, $points, $validate;
    public function __construct(Validation $validation)
    {
        $this->validate = $validation;
        $this->user = auth()->user();
        if ($this->user == null || $this->user->roles->first()->id != 5) {
            $this->validate->logout(JWTAuth::getToken());
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
            $pointsPerStation = $this->points->where('station_id', $station->id)->sum('points');
            // if ($pointsPerStation > 0)
            array_push($data['stations'], array('id' => $station->id, 'station' => "$station->name", 'points' => $pointsPerStation));
        }
        return $this->validate->successResponse('stations', $data);
    }
    // Historial de puntos por estacion
    public function dates(Station $station)
    {
        if ($station->start) {
            if ($station->end)
                return $this->validate->successResponse('dates', array('start' => $station->start, 'end' => $station->end));
            $end = new DateTime($station->start);
            $end->modify('last day of this month');
            $end = $end->format('Y-m-d');
            return $this->validate->successResponse('dates', array('start' => $station->start, 'end' => $end));
        }
        $end = new DateTime(date('Y-m') . '-01');
        $end->modify('last day of this month');
        $end = $end->format('Y-m-d');
        return $this->validate->successResponse('dates', array('start' => date('Y-m') . '-01', 'end' => $end));
    }
    // Historial de puntos por estación
    public function pointsStation(Request $request, Station $station)
    {
        $validator = Validator::make($request->only('date'), ['date' => 'required|date_format:Y-m-d']);
        if ($validator->fails())
            return $this->validate->errorResponse($validator->errors());
        $points = [];
        foreach (SalesQr::where([['client_id', $this->client->id], ['station_id', $station->id], ['active', 1]])
            ->whereDate('created_at', $request->date)->with(['station', 'status'])
            ->orderBy('created_at', 'desc')->get() as $point) {
            if ($point->status_id != 2) {
                $data['id'] = $point->id;
                $data['photo'] = asset("{$point->photo}");
            }
            $data['station'] = $point->station->number_station;
            $data['sale'] = $point->sale;
            $data['product'] = $point->product;
            $data['liters'] = "{$point->liters} litros";
            $data['hour'] = $point->created_at->format('H:i:s');
            $data['points'] = $point->points;
            $data['status'] = $point->status->name;
            $data['total'] = $point->payment;
            array_push($points, $data);
            $data = [];
        }
        return count($points) > 0 ? $this->validate->successResponse('points', $points) : $this->validate->errorResponse('Aun no tienes tickets registrados');
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
            return $this->validate->errorResponse('Esta venta no se puede editar');
        return $this->registerOrUpdateQr($request, $qr);
    }
    // Registro y actualizacion de las qr's escaneados
    private function registerOrUpdateQr(Request $request, SalesQr $qr = null)
    {
        $validate = $this->validate->validateSale($request, $qr);
        if (!is_bool($validate)) return $validate;
        $period = Period::all()->last();
        if (!$period or $period->finish) {
            return $this->validate->errorResponse('Los tickets solo se pueden escanear cuando haya iniciado una promoción');
        } else {
            if ($request->date < $period->date_start or $request->date > $period->date_end)
                return $this->validate->errorResponse('El ticket no puede ser sumado ya que no pertenece al periodo actual');
        }
        $station = Station::where('number_station', $request->station)->first();
        $request->merge([
            'client_id' => $this->client->id, 'station_id' => $station->id, 'sale' => $request->ticket,
            'product' => strtoupper($request->product), 'created_at' => $request->date
        ]);
        if ($qr) {
            $qr->update($request->except(['status_id', 'active']));
        } else {
            if (SalesQr::where([['station_id', $station->id], ['sale', $request->ticket]])->exists())
                return $this->validate->errorResponse('Este ticket ya fue registrado con anterioridad');
            $qr = SalesQr::create($request->all());
            $name = Str::random(10) . $request->file('photo')->getClientOriginalName();
            $image = "/storage/qrs/{$this->user->id}/";
            $path = public_path() . $image;
            if (!File::isDirectory($path))
                File::makeDirectory($path, 0777, true, true);
            Image::make($request->file('photo'))->resize(1000, null, function ($constraint) {
                $constraint->aspectRatio();
            })->save($path . $name);
            $qr->update(['photo' => $image . $name]);
        }
        if (ExcelSales::where([
            ['station_id', $station->id], ['ticket', $request->ticket], ['date', $request->date],
            ['product', 'like', "{$request->product}%"], ['liters', $request->liters], ['payment', $request->payment],
        ])->exists()) {
            $qr->update(['points' => 10, 'status_id' => 2]);
            if (($poinstation = $this->client->puntos->where('station_id', $station->id)->first()) != null) {
                $poinstation->points += 10;
                $poinstation->save();
            } else {
                Point::create($request->merge(['points' => 10])->only(['client_id', 'station_id', 'points']));
            }
            return $this->validate->successResponse('message', 'Se han sumado sus puntos');
        } else {
            if (ExcelSales::where([['ticket', $request->ticket], ['station_id', $station->id]])->exists()) {
                $qr->update(['status_id' => 4]);
                return $this->validate->errorResponse('Su ticket ha sido registrado, verifique los datos para sumar sus puntos correctamente');
            }
        }
        return $this->validate->successResponse('message', 'Su ticket ha sido registrado, se notificará en el momento que sea validado');
    }
}
