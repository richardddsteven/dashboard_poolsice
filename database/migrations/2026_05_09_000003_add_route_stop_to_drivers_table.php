<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            // ID jalur yang sedang dilewati supir saat ini
            $table->foreignId('current_route_stop_id')
                ->nullable()
                ->after('fcm_token')
                ->constrained('route_stops')
                ->nullOnDelete();

            // Kapan terakhir kali posisi jalur diperbarui
            $table->timestamp('route_stop_updated_at')->nullable()->after('current_route_stop_id');
        });
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropForeign(['current_route_stop_id']);
            $table->dropColumn(['current_route_stop_id', 'route_stop_updated_at']);
        });
    }
};
