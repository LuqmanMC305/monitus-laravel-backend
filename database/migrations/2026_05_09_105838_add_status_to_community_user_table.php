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
        Schema::table('community_user', function (Blueprint $table) {
            // Adding the status column after the 'role' column for better organization
            $table->string('status')->default('pending')->after('role'); //'pending', 'approved', or 'rejected' for accessing control flow
        
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('community_user', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
