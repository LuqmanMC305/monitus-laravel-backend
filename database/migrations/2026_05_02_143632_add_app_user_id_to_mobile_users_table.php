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
        Schema::table('mobile_users', function (Blueprint $table) {
            $table->foreignId('app_user_id')
              ->nullable() 
              ->after('mobile_user_id')
              ->constrained('app_users', 'app_user_id')
              ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mobile_users', function (Blueprint $table) {
            $table->dropForeign(['app_user_id']);
            $table->dropColumn('app_user_id');
        });
    }
};
