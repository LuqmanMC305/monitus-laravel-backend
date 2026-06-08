<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\AppUser;
use App\Models\MobileUser;
use App\Models\Community;
use App\Models\Alert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SelectiveDataSnapshotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Wrap everything in a database transaction to ensure data integrity
        DB::transaction(function () {

            // -----------------------------------------------------------------
            // 1. SEED USERS TABLE (Web Dashboard Admins)
            // -----------------------------------------------------------------
            $admin = User::updateOrCreate(
                ['email' => 'admin.monitus@gmail.com'],
                [
                    'name' => 'admin monitus',
                    'password' => Hash::make('password123'), // Using standard bcrypt rounds natively
                    'email_verified_at' => now(),
                    'created_at' => '2026-04-23 14:16:35',
                    'updated_at' => '2026-04-23 14:16:35',
                ]
            );

            // -----------------------------------------------------------------
            // 2. SEED APP_USERS TABLE (Mobile Authentication Profiles)
            // -----------------------------------------------------------------
            $appUser = AppUser::updateOrCreate(
                ['app_user_email' => 'luqman@gmail.com'],
                [
                    'app_user_id' => 36,
                    'app_user_name' => 'Luqman',
                    'app_user_password' => Hash::make('password123'),
                    'created_at' => '2026-05-05 14:45:27',
                    'updated_at' => '2026-05-05 14:45:27',
                ]
            );

            // -----------------------------------------------------------------
            // 3. SEED MOBILE_USERS TABLE (Hardware Device Tokens & Locations)
            // -----------------------------------------------------------------
            MobileUser::updateOrCreate(
                ['device_id' => 'BP2A.250605.031.A3'],
                [
                    'mobile_user_id' => 1,
                    'fcm_token' => 'fqprYR2pS3aMlS7rzxBfNv:APA91bEHNjOQ6R6f7gsNaCXfXu6zRUdcyjty1lM4brNylS-bIxzt1zw6qgtJdw6v1aUEZJB0Q-vI6VMj_p9SkENFtX08rSBA1kmlTMYkhslaj9KZ8nxMVVk',
                    // Using DB::raw to correctly bind the hex postgis coordinate geometry
                    'last_location' => DB::raw("ST_GeomFromWKB(decode('0101000020E6100000D48D661B6E1359402EAE4C535F811540', 'hex'), 4326)"),
                    'created_at' => '2026-05-04 13:38:34',
                    'updated_at' => '2026-06-02 12:55:00',
                    'last_location_at' => '2026-06-02 12:55:00',
                    'telegram_chat_id' => null,
                    'is_telegram_verified' => false,
                    'app_user_id' => $appUser->app_user_id, // Safely mapping our parent model key relation
                ]
            );

            // -----------------------------------------------------------------
            // 4. SEED COMMUNITIES TABLE (Target Channels)
            // -----------------------------------------------------------------
            Community::updateOrCreate(
                ['telegram_group_id' => '-1003934677185'],
                [
                    'community_name' => 'USM Security',
                    'community_description' => 'Security personnel for USM campus',
                    'community_location' => DB::raw("ST_GeomFromWKB(decode('0101000020E6100000BADA8AFD65135940C7293A92CB7F1540', 'hex'), 4326)"),
                    'telegram_link' => 'https://t.me/+wNxqD3_10144Yjhl',
                    'created_at' => '2026-04-23 14:41:35',
                    'updated_at' => '2026-04-23 14:41:35',
                ]
            );

            Community::updateOrCreate(
                ['telegram_group_id' => '-100246813579'],
                [
                    'community_name' => 'Test Community Penang',
                    'community_description' => null,
                    'community_location' => DB::raw("ST_GeomFromWKB(decode('0101000020E6100000BADA8AFD65135940C7293A92CB7F1540', 'hex'), 4326)"),
                    'telegram_link' => null,
                    'created_at' => '2026-05-07 17:24:40',
                    'updated_at' => '2026-05-07 17:24:40',
                ]
            );

            // -----------------------------------------------------------------
            // 5. SEED ALERTS TABLE (Historical Crisis Database Records)
            // -----------------------------------------------------------------
            // Alert 120: Polygon Flood Warning
            Alert::updateOrCreate(
                [
                    'admin_id' => $admin->id,
                    'title' => '⚠️ Severe Flash Flood Warning: Sungai Pinang Overflow'
                ],
                [
                    'instruction' => 'Torrential rainfall has caused local river levels to breach critical thresholds. Avoid all ground-level basements and parking structures. Evacuate to designated higher ground or upper floors immediately. Emergency response teams are stationed at Zone B.',
                    'status' => 'resolved',
                    'severity' => 'HIGH',
                    'latitude' => null,
                    'longitude' => null,
                    'radius' => null,
                    'danger_zone' => null,
                    'area_type' => 'polygon',
                    'danger_zone_coordinates' => '[[5.3767009348271575,100.30269742012024],[5.376530028710349,100.3071177005768],[5.374051884625826,100.30741810798646],[5.374457788226841,100.30046582221986]]',
                    'alert_category' => 'flood',
                    'category_icon' => '🌊',
                    'created_at' => '2026-06-02 12:52:35',
                    'updated_at' => '2026-06-02 12:56:07',
                ]
            );

            // Alert 121: Radial Fire Hazard Warning
            Alert::updateOrCreate(
                [
                    'admin_id' => $admin->id,
                    'title' => '🔥 Hazardous Material Isolation: Block G Chemistry Lab'
                ],
                [
                    'instruction' => 'A localized chemical containment breach has occurred in Block G. A strict 200-metre safety perimeter is now active. Do not enter the area. If you are inside nearby buildings, seal all windows, shut down ventilation systems, and await further updates.',
                    'status' => 'resolved',
                    'severity' => 'MEDIUM',
                    'latitude' => '5.37488505',
                    'longitude' => '100.29880285',
                    'radius' => 740,
                    'danger_zone' => null,
                    'area_type' => 'radius',
                    'danger_zone_coordinates' => null,
                    'alert_category' => 'fire',
                    'category_icon' => '🔥',
                    'created_at' => '2026-06-02 12:53:22',
                    'updated_at' => '2026-06-02 12:55:59',
                ]
            );

        });
    }
}