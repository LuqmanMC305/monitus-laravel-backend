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
        Schema::create('alerts', function (Blueprint $table) {
            $table->id('alert_id'); //PK

            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade'); // FK to the Admin

            // Alert Attributes
            $table->string('title'); // Alert Title
            $table->text('instruction'); // Detailed instructions
            $table->string('status')->default('active'); // status
            $table->string('severity')->default('medium'); // severity

            // Geographical Data
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->integer('radius'); // radius in meters
            
            // PostGIS column for spatial queries
            $table->geometry('danger_zone', subtype: 'point', srid: 4326)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
