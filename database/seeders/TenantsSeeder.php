<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TenantsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('tenants')->insert([
            [
                'owner_id' => 3,
                'company_name' => 'Wyatt and Hancock Traders',
                'plan' => 'Free',
                'settings' => '{"style": "friendly", "voice": "Polly.Joanna", "memory": true}',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
