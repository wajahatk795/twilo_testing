<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CallSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('calls')->insert([
            [
                'tenant_id' => 1,
                'lead_id' => 1,
                'call_sid' => 'CA1234567890abcdef1234567890abcdef',
                'from' => '+12345678901',
                'to' => '+19876543210',
                'call_type' => 'Phone',
                'duration' => json_encode(['time' => '00:03:20']),
                'status' => 'queued',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => 1,
                'lead_id' => 1,
                'call_sid' => 'CAabcdef1234567890abcdef1234567890',
                'from' => '+11122233344',
                'to' => '+14443332211',
                'call_type' => 'Phone',
                'duration' => json_encode(['time' => '00:01:45']),
                'status' => 'completed',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => 1,
                'lead_id' => 1,
                'call_sid' => 'CA0987654321abcdef0987654321abcdef',
                'from' => '+15556667777',
                'to' => '+17778889999',
                'call_type' => 'Phone',
                'duration' => json_encode(['time' => '00:05:10']),
                'status' => 'completed',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => 1,
                'lead_id' => 1,
                'call_sid' => 'CA11223344556677889900aabbccddeeff',
                'from' => '+19998887777',
                'to' => '+18887776655',
                'call_type' => 'Phone',
                'duration' => json_encode(['time' => '00:00:50']),
                'status' => 'completed',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => 1,
                'lead_id' => 1,
                'call_sid' => 'CAa1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6',
                'from' => '+16667778888',
                'to' => '+15554443333',
                'call_type' => 'Phone',
                'duration' => json_encode(['time' => '00:02:22']),
                'status' => 'in-progress',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
