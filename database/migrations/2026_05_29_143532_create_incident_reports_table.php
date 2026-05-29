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
        Schema::create('incident_reports', function (Blueprint $table) {
            $table->id();

            // Foreign Key link to APP_USERS (One User -> Many Reports)
            // If an app user deletes their account, cascade will clean up their raw reports automatically
            $table->foreignId('app_user_id')
                  ->constrained('app_users', 'app_user_id')
                  ->onDelete('cascade');

            // Geospatial Data Metrics
            $table->string('title')->nullable(); // Brief title or category name picker (e.g. "Flash Flood")
            $table->text('incident_description'); 
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            
            // Optional geofencing data if you want to let users suggest a size, 
            // otherwise default to a circle marker location point
            $table->string('area_type')->default('radius'); 
            $table->integer('radius')->default(500);

            // Image uploads storage path pointer string
            $table->string('image_path')->nullable(); 

            // AI Triage Assessment Modifiers
            $table->decimal('llm_spam_score', 3, 2)->default(0.00); // Stores confidence values like 0.85
            
            // Lifecycle Workflow State Tracking
            // States: 'pending', 'approved', 'rejected'
            $table->string('status')->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incident_reports');
    }
};
