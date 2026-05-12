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
        Schema::create('delivery_logs', function (Blueprint $table) {
            $table->id('log_id'); // PK:

            $table->foreignId('alert_id') // FK1 (Linking to alerts table)
                  ->constrained('alerts', 'alert_id')
                  ->onDelete('cascade');

            $table->foreignId('mobile_user_id') // FK2 (Linking to mobile_users table)
                  ->constrained('mobile_users', 'mobile_user_id')
                  ->onDelete('cascade');

            $table->boolean('is_success')->default(true);
            $table->timestamp('delivered_at')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_logs');
    }
};
