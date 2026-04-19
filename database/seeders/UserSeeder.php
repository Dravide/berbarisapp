<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Administrator',
            'username' => 'admin',
            'email' => 'admin@baris.app',
            'password' => Hash::make('password'),
            'role' => 'Admin',
        ]);

        User::create([
            'name' => 'Eventner User',
            'username' => 'eventner',
            'email' => 'eventner@baris.app',
            'password' => Hash::make('password'),
            'role' => 'Eventner',
        ]);

        User::create([
            'name' => 'Peserta User',
            'username' => 'peserta',
            'email' => 'peserta@baris.app',
            'password' => Hash::make('password'),
            'role' => 'Peserta',
        ]);
    }
}
