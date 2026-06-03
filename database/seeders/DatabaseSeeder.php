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
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        /* 🟢 Execute table seeders sequentially to preserve foreign key constraints
        $this->call([
            UserSeeder::class,               // 1. Parent: Populates 'users' table
            CommunitySeeder::class,          // 2. Parent: Populates 'communities' table
            AlertSeeder::class,              // 3. Child: Populates 'alerts' table (belongs to users)
            CommunityBroadcastSeeder::class, // 4. Junction: Links alerts and communities
        ]);
        */
    }
}
