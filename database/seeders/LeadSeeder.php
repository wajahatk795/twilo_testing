<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('leads')->insert([
            [
                'user_id' => 1,
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '+12345678901',
                'metadata' => json_encode(['note' => 'Interested in product A']),
                'source' => 'inbound',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
