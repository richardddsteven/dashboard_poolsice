<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom GPS aktual supir agar sorting antrian order
     * bisa menggunakan posisi presisi supir (bukan hanya titik tengah route stop).
     *
     * Supir sudah melaporkan koordinat GPS setiap 2 menit via POST /api/driver/route-stop.
     * Koordinat itu kini disimpan langsung ke tabel drivers.
     */
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->decimal('current_latitude', 10, 7)->nullable()->after('current_route_stop_id');
            $table->decimal('current_longitude', 10, 7)->nullable()->after('current_latitude');
        });
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn(['current_latitude', 'current_longitude']);
        });
    }
};
