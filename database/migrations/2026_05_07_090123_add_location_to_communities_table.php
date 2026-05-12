<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('communities', function (Blueprint $table) {
            // geography point column (4326 is the standard GPS SRID)
            $table->geography('community_location', 'point', 4326)->nullable()->after('community_name');
            
            // spatial index for high-performance searching
            $table->spatialIndex('community_location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('communities', function (Blueprint $table) {
            // Drop the spatial index first (standard SQL practice)
            $table->dropSpatialIndex(['community_location']);
            
            // Drop the column itself
            $table->dropColumn('community_location');
        });
    }
};
