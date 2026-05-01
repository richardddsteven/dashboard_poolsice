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
        if (Schema::hasTable('ice_type_stocks')) {
            Schema::table('ice_type_stocks', function (Blueprint $table) {
                $table->dropUnique('ice_type_stocks_ice_type_id_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('ice_type_stocks')) {
            Schema::table('ice_type_stocks', function (Blueprint $table) {
                $table->unique('ice_type_id', 'ice_type_stocks_ice_type_id_unique');
            });
        }
    }
};