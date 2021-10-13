<?php

use App\Role;
use Illuminate\Database\Seeder;

class RoleTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Role::create(
            [
                'name' => 'admin_master',
                'description' => 'Administrador de la empresa DigitalSoft',
                'display_name' => 'Administrador Master'
            ]
        );
        Role::create(
            [
                'name' => 'admin_eucomb',
                'description' => 'Administrador General',
                'display_name' => 'Administrador Eucomb'
            ]
        );
        Role::create(
            [
                'name' => 'admin_estacion',
                'description' => 'Administrador de estación',
                'display_name' => 'Administrador Eucomb Vales y Premios'
            ]
        );
        Role::create(
            [
                'id' => 5,
                'name' => 'usuario',
                'description' => 'Usuarios',
                'display_name' => 'Usuarios o Clientes'
            ]
        );
    }
}
