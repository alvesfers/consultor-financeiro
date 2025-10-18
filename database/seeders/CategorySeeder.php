<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Cria usuário do cliente
        $userId = DB::table('users')->insertGetId([
            'name' => 'Maria',
            'email' => 'cliente@gmail.com',
            'email_verified_at' => now(),
            'password' => Hash::make('1234'),
            'remember_token' => Str::random(60),
            'role' => 'client',
            'timezone' => 'America/Sao_Paulo',
            'locale' => 'pt_BR',
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Cria vínculo na tabela clients
        DB::table('clients')->insert([
            'user_id' => $userId,
            'consultant_id' => 2, // João Consultor
            'status' => 'ativo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
