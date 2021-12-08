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
use Illuminate\Support\Facades\File;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Str;

class ClientController extends Controller
{
    private $user, $client, $validate;
    public function __construct(Validation $validation)
    {
        $this->validate = $validation;
        $this->user = auth()->user();
        !$this->user || $this->user->roles->first()->id != 5 ?
            $this->validate->logout(JWTAuth::getToken()) :
            $this->client = $this->user->client;
    }
    // funcion para obtener informacion del usuario hacia la pagina princial
    public function index()
    {
        $data['id'] = $this->client->id;
        $data['name'] = "{$this->user->name} {$this->user->first_surname} {$this->user->second_surname}";
        $data['membership'] = $this->user->membership;
        $data['stations'] = [];
        foreach (Station::where('active', 1)->get() as $station) {
            $pointsPerStation = $this->client->puntos->where('station_id', $station->id)->sum('points');
            array_push(
                $data['stations'],
                ['id' => $station->id, 'station' => $station->name, 'points' => $pointsPerStation]
            );
        }
        return $this->validate->successResponse('stations', $data);
    }
    // Historial de puntos por estacion
    public function dates()
    {
        $period = Period::all()->last();
        if ($period and !$period->finish) {
            return $this->validate->successResponse(
                'dates',
                ['start' => $period->date_start, 'end' => $period->date_end]
            );
        }
        return $this->validate->errorResponse(
            $period ? '"El periodo de promoción ha finalizado, pronto daremos a conocer a nuestros ganadores"' :
                '"Aún no ha iniciado un periodo de promoción."'
        );
    }
    // Historial de puntos por estación
    public function pointsStation(Request $request, Station $station)
    {
        $validator = Validator::make($request->only('date'), ['date' => 'required|date_format:Y-m-d']);
        if ($validator->fails()) return $this->validate->errorResponse($validator->errors());
        $points = [];
        foreach ($this->client->qrs()->whereDate('created_at', $request->date)
            ->where('station_id', $station->id)->with('status')->orderBy('created_at', 'desc')
            ->get() as $point) {
            if ($point->status_id != 2) {
                $data['id'] = $point->id;
                $data['photo'] = asset("{$point->photo}");
            }
            $data['station'] = $station->number_station;
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
        return $qr->status_id != 2 ? $this->registerOrUpdateQr($request, $qr) :
            $this->validate->errorResponse('Esta venta no se puede editar');
    }
    // Términos y condiciones
    public function termsAndConditions()
    {
        return ($period = Period::all()->last()) ?
            $this->validate->successResponse('message', $period->terms) :
            $this->validate->errorResponse('Aun no hay periodos de promoción activos');
    }
    // Registro y actualizacion de las qr's escaneados
    private function registerOrUpdateQr(Request $request, SalesQr $qr = null)
    {
        $validate = $this->validate->validateSale($request, $qr);
        if (!is_bool($validate)) return $validate;
        $period = Period::all()->last();
        if (!$period or $period->finish) {
            return $this->validate
                ->errorResponse('El periodo de promoción ha finalizado, pronto daremos a conocer a nuestros ganadores.');
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
            $qr->update($request->except(['status_id']));
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
            // Posible cambio en la suma de puntos
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
