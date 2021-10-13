<?php

use App\User;
use Illuminate\Database\Seeder;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::create([
            'name' => 'Administrador',
            'first_surname' => 'del',
            'second_surname' => 'sistema',
            'membership' => 'MO20210001',
            'email' => 'administrador@correo.com',
            'phone' => '2221234567',
            'password' => bcrypt('secret'),
        ]);
        $user->roles()->sync(1);
    }
}
