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
        Schema::table('alerts', function (Blueprint $table) {

            // 1. Define the spatial drawing configuration type
            $table->string('area_type')->default('radius')->after('radius');

            // 2. LONGTEXT handles massive sets of polygon coordinate vertices smoothly
            $table->longText('danger_zone_coordinates')->nullable()->after('area_type');

            // 3. Category classification and frontend emoji presentation fields
            $table->string('alert_category')->nullable()->after('danger_zone_coordinates');
            $table->string('category_icon', 50)->nullable()->after('alert_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->dropColumn([
                'area_type',
                'danger_zone_coordinates',
                'alert_category',
                'category_icon'
            ]);
        });
    }
};
