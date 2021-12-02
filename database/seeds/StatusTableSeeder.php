<?php

use App\Api\Status;
use Illuminate\Database\Seeder;

class StatusTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Status::create(['name' => 'Pendiente']);
        Status::create(['name' => 'Sumado']);
        Status::create(['name' => 'No válido']);
        Status::create(['name' => 'Verificar']);
    }
}
