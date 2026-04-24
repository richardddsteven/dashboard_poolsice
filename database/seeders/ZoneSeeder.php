<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ZoneSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $zones = [
            ['name' => 'Canggu', 'latitude' => -8.6483000, 'longitude' => 115.1385000],
            ['name' => 'Jimbaran', 'latitude' => -8.7908000, 'longitude' => 115.1660000],
            ['name' => 'Uluwatu', 'latitude' => -8.8291000, 'longitude' => 115.0849000],
            ['name' => 'Tabanan', 'latitude' => -8.5395000, 'longitude' => 115.1249000],
            ['name' => 'Denpasar', 'latitude' => -8.6705000, 'longitude' => 115.2126000],
        ];

        foreach ($zones as $zone) {
            DB::table('zones')->updateOrInsert(
                ['name' => $zone['name']],
                [
                    'latitude' => $zone['latitude'],
                    'longitude' => $zone['longitude'],
                    'updated_at' => $now,
                ]
            );
        }
    }
}
