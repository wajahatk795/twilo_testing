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
            'name' => 'admin',
            'email' => 'admin@gmail.com',
            'role_id' => 1,
            'password' => '$2y$12$9YiI7bvwAX6ga4OiSwh9J.MFtDgCH0kXmi6NzgMKYmeiBQw0z1Kjq',
        ]);


        $this->call([
            LeadSeeder::class,
            TenantsSeeder::class,
            CallSeeder::class,
        ]);
    }
}
