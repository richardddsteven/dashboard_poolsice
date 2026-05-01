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
        // Create ice_type_driver_stock table untuk tracking stok per jenis es
        Schema::create('ice_type_driver_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('drivers')->cascadeOnDelete();
            $table->foreignId('ice_type_id')->constrained('ice_types')->cascadeOnDelete();
            $table->date('date');
            $table->integer('quantity')->default(0)->unsigned();
            $table->timestamps();

            // Pastikan hanya satu entry per driver per ice_type per hari
            $table->unique(['driver_id', 'ice_type_id', 'date']);
            $table->index(['driver_id', 'date']);
            $table->index(['date']);
        });

        // Migrasi data dari driver_stocks table jika ada
        if (Schema::hasTable('driver_stocks')) {
            // Tentukan mapping ice_type untuk data lama
            \DB::transaction(function () {
                $oldStocks = \DB::table('driver_stocks')->get();

                foreach ($oldStocks as $oldStock) {
                    // Insert 5kg stock
                    if ($oldStock->stock_5kg > 0) {
                        $iceType5kg = \DB::table('ice_types')
                            ->where('weight', 5)
                            ->first();

                        if ($iceType5kg) {
                            \DB::table('ice_type_driver_stocks')->insert([
                                'driver_id' => $oldStock->driver_id,
                                'ice_type_id' => $iceType5kg->id,
                                'date' => $oldStock->date,
                                'quantity' => $oldStock->stock_5kg,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }

                    // Insert 20kg stock
                    if ($oldStock->stock_20kg > 0) {
                        $iceType20kg = \DB::table('ice_types')
                            ->where('weight', 20)
                            ->first();

                        if ($iceType20kg) {
                            \DB::table('ice_type_driver_stocks')->insert([
                                'driver_id' => $oldStock->driver_id,
                                'ice_type_id' => $iceType20kg->id,
                                'date' => $oldStock->date,
                                'quantity' => $oldStock->stock_20kg,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ice_type_driver_stocks');
    }
};
