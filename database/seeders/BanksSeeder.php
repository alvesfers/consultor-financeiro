<?php

// database/seeders/BanksSeeder.php

namespace Database\Seeders;

use App\Models\Bank;
use Illuminate\Database\Seeder;

class BanksSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['name' => 'Nubank',    'slug' => 'nubank',   'code' => '260', 'logo_svg' => 'banks/nubank.svg',   'color_primary' => '#7F3DFF', 'color_secondary' => '#BDA5FF', 'color_bg' => '#F5F1FF', 'color_text' => '#2E1065'],
            ['name' => 'Itaú',      'slug' => 'itau',     'code' => '341', 'logo_svg' => 'banks/itau.svg',     'color_primary' => '#F47920', 'color_secondary' => '#003399', 'color_bg' => '#FFF3E9', 'color_text' => '#1F2937'],
            ['name' => 'Bradesco',  'slug' => 'bradesco', 'code' => '237', 'logo_svg' => 'banks/bradesco.svg', 'color_primary' => '#CC092F', 'color_secondary' => '#FFFFFF', 'color_bg' => '#FFF0F3', 'color_text' => '#7F1D1D'],
            ['name' => 'Santander', 'slug' => 'santander', 'code' => '033', 'logo_svg' => 'banks/santander.svg', 'color_primary' => '#EC0000', 'color_secondary' => '#FFFFFF', 'color_bg' => '#FFF1F1', 'color_text' => '#7F1D1D'],
            ['name' => 'Caixa',     'slug' => 'caixa',    'code' => '104', 'logo_svg' => 'banks/caixa.svg',    'color_primary' => '#005CAA', 'color_secondary' => '#F58220', 'color_bg' => '#EFF6FF', 'color_text' => '#0F172A'],
            ['name' => 'Inter',     'slug' => 'inter',    'code' => '077', 'logo_svg' => 'banks/inter.svg',    'color_primary' => '#FF6B00', 'color_secondary' => '#111827', 'color_bg' => '#FFF5EC', 'color_text' => '#1F2937'],
            ['name' => 'C6 Bank',   'slug' => 'c6',       'code' => '336', 'logo_svg' => 'banks/c6.svg',       'color_primary' => '#111111', 'color_secondary' => '#9CA3AF', 'color_bg' => '#F3F4F6', 'color_text' => '#111827'],
            ['name' => 'BTG',       'slug' => 'btg',      'code' => '208', 'logo_svg' => 'banks/btg.svg',      'color_primary' => '#0D2C6C', 'color_secondary' => '#2563EB', 'color_bg' => '#EFF6FF', 'color_text' => '#0F172A'],
            ['name' => 'Sicoob',    'slug' => 'sicoob',   'code' => '756', 'logo_svg' => 'banks/sicoob.svg',   'color_primary' => '#1A8F6F', 'color_secondary' => '#03383E', 'color_bg' => '#ECFDF5', 'color_text' => '#064E3B'],
            ['name' => 'Sicredi',   'slug' => 'sicredi',  'code' => '748', 'logo_svg' => 'banks/sicredi.svg',  'color_primary' => '#74B72E', 'color_secondary' => '#14532D', 'color_bg' => '#F0FDF4', 'color_text' => '#14532D'],
            ['name' => 'Mercado Pago', 'slug' => 'mercado-pago', 'code' => null, 'logo_svg' => 'banks/mercado-pago.svg', 'color_primary' => '#00AEEF', 'color_secondary' => '#003E6B', 'color_bg' => '#ECFEFF', 'color_text' => '#0C4A6E'],
        ];

        foreach ($data as $b) {
            Bank::updateOrCreate(['slug' => $b['slug']], $b);
        }
    }
}
