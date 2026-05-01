<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('ice_type_stocks') && !Schema::hasColumn('ice_type_stocks', 'date')) {
            Schema::table('ice_type_stocks', function (Blueprint $table) {
                $table->date('date')->nullable()->after('ice_type_id');
            });

            DB::table('ice_type_stocks')
                ->whereNull('date')
                ->orWhere('date', '0000-00-00')
                ->update(['date' => now()->toDateString()]);

            Schema::table('ice_type_stocks', function (Blueprint $table) {
                $table->date('date')->nullable(false)->change();
                $table->unique(['ice_type_id', 'date'], 'ice_type_stocks_type_date_unique');
                $table->index(['date'], 'ice_type_stocks_date_index');
            });
        }

        if (Schema::hasTable('ice_type_driver_stocks') && !Schema::hasColumn('ice_type_driver_stocks', 'date')) {
            Schema::table('ice_type_driver_stocks', function (Blueprint $table) {
                $table->date('date')->nullable()->after('ice_type_id');
            });

            DB::table('ice_type_driver_stocks')
                ->whereNull('date')
                ->orWhere('date', '0000-00-00')
                ->update(['date' => now()->toDateString()]);

            Schema::table('ice_type_driver_stocks', function (Blueprint $table) {
                $table->date('date')->nullable(false)->change();
                $table->unique(['driver_id', 'ice_type_id', 'date'], 'itds_driver_ice_date_unique');
                $table->index(['driver_id', 'date'], 'itds_driver_date_index');
                $table->index(['date'], 'itds_date_index');
            });
        }

        if (Schema::hasTable('driver_stocks') && !Schema::hasColumn('driver_stocks', 'date')) {
            Schema::table('driver_stocks', function (Blueprint $table) {
                $table->date('date')->nullable()->after('driver_id');
            });

            DB::table('driver_stocks')
                ->whereNull('date')
                ->orWhere('date', '0000-00-00')
                ->update(['date' => now()->toDateString()]);

            Schema::table('driver_stocks', function (Blueprint $table) {
                $table->date('date')->nullable(false)->change();
                $table->unique(['driver_id', 'date'], 'driver_stocks_driver_date_unique');
                $table->index('date', 'driver_stocks_date_index');
            });
        }

        if (Schema::hasTable('stocks') && !Schema::hasColumn('stocks', 'date')) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->date('date')->nullable()->after('id');
            });

            DB::table('stocks')
                ->whereNull('date')
                ->orWhere('date', '0000-00-00')
                ->update(['date' => now()->toDateString()]);

            Schema::table('stocks', function (Blueprint $table) {
                $table->date('date')->nullable(false)->change();
                $table->unique('date', 'stocks_date_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('stocks') && Schema::hasColumn('stocks', 'date')) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->dropUnique('stocks_date_unique');
                $table->dropColumn('date');
            });
        }

        if (Schema::hasTable('driver_stocks') && Schema::hasColumn('driver_stocks', 'date')) {
            Schema::table('driver_stocks', function (Blueprint $table) {
                $table->dropIndex('driver_stocks_date_index');
                $table->dropUnique('driver_stocks_driver_date_unique');
                $table->dropColumn('date');
            });
        }

        if (Schema::hasTable('ice_type_driver_stocks') && Schema::hasColumn('ice_type_driver_stocks', 'date')) {
            Schema::table('ice_type_driver_stocks', function (Blueprint $table) {
                $table->dropIndex('itds_date_index');
                $table->dropIndex('itds_driver_date_index');
                $table->dropUnique('itds_driver_ice_date_unique');
                $table->dropColumn('date');
            });
        }

        if (Schema::hasTable('ice_type_stocks') && Schema::hasColumn('ice_type_stocks', 'date')) {
            Schema::table('ice_type_stocks', function (Blueprint $table) {
                $table->dropIndex('ice_type_stocks_date_index');
                $table->dropUnique('ice_type_stocks_type_date_unique');
                $table->dropColumn('date');
            });
        }
    }
};
