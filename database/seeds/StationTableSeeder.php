<?php

use App\Station;
use Illuminate\Database\Seeder;

class StationTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Station::create(
            [
                'name' => 'Aldía Cholula',
                'address' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit',
                'phone' => '222123123',
                'email' => 'aldiacholula@correo.com',
                'number_station' => '6532',
            ]
        );
        Station::create(
            [
                'name' => 'Vanoe',
                'address' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit',
                'phone' => '222123122',
                'email' => 'vanoe@correo.com',
                'number_station' => '13771',
            ]
        );
        Station::create(
            [
                'name' => 'Las ánimas',
                'address' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit',
                'phone' => '222123121',
                'email' => 'lasanimas@correo.com',
                'number_station' => '5286',
            ]
        );
        Station::create(
            [
                'name' => 'Aldía dorada',
                'address' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit',
                'phone' => '222123120',
                'email' => 'aldiadorada@correo.com',
                'number_station' => '5391',
            ]
        );
    }
}
