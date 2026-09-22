<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolesAndAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $role = Role::firstOrCreate(
            ['nombre' => 'Administrador'],
            ['descripcion' => 'Acceso total al sistema', 'estado' => 'activo']
        );

        User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin',
                'apellido' => 'Principal',
                'password' => Hash::make('password'),
                'estado' => 'activo',
                'role_id' => $role->id,
            ]
        );
    }
}
