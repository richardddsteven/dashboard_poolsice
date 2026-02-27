<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Servis Mobil',
            'Bayar Listrik',
            'Service Mesin',
            'Bensin Kendaraan',
            'Lainnya'
        ];

        foreach ($categories as $category) {
            \App\Models\ExpenseCategory::create(['name' => $category]);
        }
    }
}
