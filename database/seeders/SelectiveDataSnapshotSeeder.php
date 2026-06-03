<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SelectiveDataSnapshotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            
            /*
            // 1. Create the Authorised Administrator entry
            $admin = User::create([
                'name' => 'Admin Luqman',
                'email' => 'admin.luqman@usm.my',
                'password' => bcrypt('SecurePassword123'),
                'admin_role' => 'super_admin',
            ]);

            // 2. Create a Target Community entry
            $community = Community::create([
                'community_name' => 'USM Security',
                'telegram_group_id' => '-100213456789',
                'community_description' => 'Security personnel for USM campus',
            ]);

            // 3. Create a location-specific Alert belonging to the Admin
            $alert = Alert::create([
                'admin_id' => $admin->id, // References our newly created admin ID
                'title' => '⚠️ Severe Flash Flood Warning: Sungai Pinang Overflow',
                'instruction' => 'Avoid all ground-level basements. Evacuate to designated higher ground immediately.',
                'status' => 'ACTIVE',
                'severity' => 'HIGH',
                'latitude' => 5.35500000,
                'longitude' => 100.30130000,
                'radius' => 1000,
            ]);

            // 4. Create the Junction Log row linking the Alert to the Telegram Community
            DB::table('community_broadcasts')->insert([
                'alert_id' => $alert->id,
                'community_id' => $community->id,
                'community_status' => 'delivered',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            */
        });
    }
}
