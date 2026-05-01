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
        // Create ice_type_stocks table untuk tracking stok admin per ice type per hari
        Schema::create('ice_type_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ice_type_id')->constrained('ice_types')->cascadeOnDelete();
            $table->date('date');
            $table->integer('quantity')->default(0)->unsigned();
            $table->timestamps();

            // Unique constraint: satu entry per ice_type per hari
            $table->unique(['ice_type_id', 'date']);
            $table->index(['date']);
        });

        // Migrate data dari stocks table jika ada
        if (Schema::hasTable('stocks') && Schema::hasColumns('stocks', ['stock_5kg', 'stock_20kg'])) {
            \DB::transaction(function () {
                $oldStocks = \DB::table('stocks')->get();

                foreach ($oldStocks as $oldStock) {
                    // Insert 5kg stock
                    if ($oldStock->stock_5kg > 0) {
                        $iceType5kg = \DB::table('ice_types')
                            ->where('weight', 5)
                            ->first();

                        if ($iceType5kg) {
                            \DB::table('ice_type_stocks')->insert([
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
                            \DB::table('ice_type_stocks')->insert([
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
        Schema::dropIfExists('ice_type_stocks');
    }
};
