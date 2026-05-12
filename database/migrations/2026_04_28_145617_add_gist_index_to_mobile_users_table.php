<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mobile_users', function (Blueprint $table) {
            // Use raw statement to create GIST index
            // This assumes column name is 'last_location'
            DB::statement('CREATE INDEX mobile_users_last_location_gist ON mobile_users USING GIST (last_location)');
        });

        /*
         LOCATION COLUMN HASN'T EXIST YET IN COMMUNITIES TABLE
        Schema::table('communities', function (Blueprint $table) {
            // Adding it to the communities table as well for the 'location' column
            DB::statement('CREATE INDEX communities_location_gist ON communities USING GIST (location)');
        });
        */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mobile_users', function (Blueprint $table) {
            $table->dropIndex('mobile_users_last_location_gist');
        });
    }
};
