<?php

use App\Menu;
use Illuminate\Database\Seeder;

class MenuTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $menu = Menu::create([
            'name_modulo' => 'dashboard',
            'desplegable' => 0,
            'ruta' => 'home',
            'id_role' => 0,
            'icono' => 'icon-chart-pie-36'
        ]);
        $menu->roles()->attach([1, 2, 3]);

        $menu = Menu::create([
            'name_modulo' => 'Perfil',
            'desplegable' => 0,
            'ruta' => 'profile',
            'id_role' => 0,
            'icono' => 'icon-single-02'
        ]);
        $menu->roles()->attach([1, 2, 3]);

        $menu = Menu::create([
            'name_modulo' => 'Administradores',
            'desplegable' => 0,
            'ruta' => 'admins',
            'id_role' => 1,
            'icono' => 'supervisor_account'
        ]);
        $menu->roles()->attach([1, 2]);

        $menu = Menu::create([
            'name_modulo' => 'Clientes',
            'desplegable' => 0,
            'ruta' => 'clients',
            'id_role' => 1,
            'icono' => 'people_alt'
        ]);
        $menu->roles()->attach([1, 2, 3]);


        $menu = Menu::create([
            'name_modulo' => 'Estaciones',
            'desplegable' => 0,
            'ruta' => 'stations',
            'id_role' => 1,
            'icono' => 'local_gas_station'
        ]);
        $menu->roles()->attach([1, 2]);

        $menu = Menu::create([
            'name_modulo' => 'Elegir ganador',
            'desplegable' => 0,
            'ruta' => 'winners',
            'id_role' => 2,
            'icono' => 'fa-trophy'
        ]);
        $menu->roles()->attach([1, 2, 3]);

        $menu = Menu::create([
            'name_modulo' => 'Historial',
            'desplegable' => 0,
            'ruta' => 'history',
            'id_role' => 1,
            'icono' => 'event_note'
        ]);
        $menu->roles()->attach([1, 2]);
    }
}
