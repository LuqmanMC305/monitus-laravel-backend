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
        Schema::create('mobile_users', function (Blueprint $table) {
            $table->id('mobile_user_id');
            $table->string('device_id')->unique(); // Tracking Unique device
            $table->string('fcm_token')->nullable(); // For sending emergency alerts
            // PostGIS implementation
            $table->geography('last_location', 'point', 4326)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mobile_users');
    }
};
