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
        if (Schema::hasTable('ice_type_driver_stocks')) {
            Schema::table('ice_type_driver_stocks', function (Blueprint $table) {
                $table->dropUnique('itds_driver_ice_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('ice_type_driver_stocks')) {
            Schema::table('ice_type_driver_stocks', function (Blueprint $table) {
                $table->unique(['driver_id', 'ice_type_id'], 'itds_driver_ice_unique');
            });
        }
    }
};