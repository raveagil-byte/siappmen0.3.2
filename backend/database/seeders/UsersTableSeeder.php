<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin CSSD',
                'email' => 'admin@cssd.com',
                'password' => Hash::make('password'),
                'role' => 'admin_cssd',
                'phone' => '081234567890',
                'is_active' => true,
            ],
            [
                'name' => 'Petugas CSSD 1',
                'email' => 'petugas@cssd.com',
                'password' => Hash::make('password'),
                'role' => 'petugas_cssd',
                'phone' => '081234567891',
                'is_active' => true,
            ],
            [
                'name' => 'Petugas CSSD 2',
                'email' => 'petugas2@cssd.com',
                'password' => Hash::make('password'),
                'role' => 'petugas_cssd',
                'phone' => '081234567892',
                'is_active' => true,
            ],
            [
                'name' => 'Perawat Unit OK',
                'email' => 'perawat@unit.com',
                'password' => Hash::make('password'),
                'role' => 'perawat_unit',
                'phone' => '081234567893',
                'is_active' => true,
            ],
            [
                'name' => 'Perawat Unit ICU',
                'email' => 'perawat.icu@unit.com',
                'password' => Hash::make('password'),
                'role' => 'perawat_unit',
                'phone' => '081234567894',
                'is_active' => true,
            ],
            [
                'name' => 'Supervisor CSSD',
                'email' => 'supervisor@cssd.com',
                'password' => Hash::make('password'),
                'role' => 'supervisor',
                'phone' => '081234567895',
                'is_active' => true,
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }
    }
}