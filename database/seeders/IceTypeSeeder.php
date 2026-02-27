<?php

namespace Database\Seeders;

use App\Models\IceType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class IceTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $iceTypes = [
            [
                'name' => '5kg',
                'description' => 'Es batu 5 kilogram',
                'weight' => 5.00,
                'price' => 6000,
                'is_active' => true,
            ],
            [
                'name' => '20kg',
                'description' => 'Es batu 20 kilogram',
                'weight' => 20.00,
                'price' => 17000,
                'is_active' => true,
            ]
        ];

        foreach ($iceTypes as $iceType) {
            IceType::firstOrCreate(
                ['name' => $iceType['name']], // Cari berdasarkan name
                $iceType // Data untuk create jika tidak ada
            );
        }
    }
}
