<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate([
            'email' => 'admin@poolsice.com',
        ], [
            'name' => 'Admin Pools Ice',
            'password' => Hash::make('adminpools123'),
        ]);

        $this->call(ZoneSeeder::class);
        $this->call(IceTypeSeeder::class);
        $this->call(ExpenseCategorySeeder::class);
    }
}
