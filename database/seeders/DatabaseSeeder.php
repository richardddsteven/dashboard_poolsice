<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Admin Pools Ice',
            'email' => 'admin@poolsice.com',
            'password' => bcrypt('adminpools123'),
        ]);

        $this->call(ZoneSeeder::class);
        $this->call(IceTypeSeeder::class);
        $this->call(ExpenseCategorySeeder::class);
    }
}
