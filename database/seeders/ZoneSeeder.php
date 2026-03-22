<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ZoneSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('zones')->insert([
            ['name' => 'Canggu'],
            ['name' => 'Jimbaran'],
            ['name' => 'Uluwatu'],
            ['name'=> 'Tabanan'],
            ['name' => 'Denpasar'],
        ]);
    }
}
