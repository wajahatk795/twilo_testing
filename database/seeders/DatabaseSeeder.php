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
            'password' => '$2a$12$zCgXt/LRkbfm4Aq8p5siG.VbBtRy8f0Pwky6JISvIu8/2UCMg7v7i',
        ]);


        $this->call([
            LeadSeeder::class,
            TenantsSeeder::class,
            CallSeeder::class,
        ]);
    }
}
