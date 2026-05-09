<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('route_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('zones')->cascadeOnDelete();
            $table->string('name');           // Jalur A, Jalur B, Jalur C, dst
            $table->unsignedSmallInteger('order_index'); // Urutan perjalanan: 1, 2, 3
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedInteger('radius_meters')->default(500); // Radius cakupan jalur
            $table->timestamps();

            $table->unique(['zone_id', 'order_index']); // Satu urutan unik per zona
            $table->index(['zone_id', 'order_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_stops');
    }
};
