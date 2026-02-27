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
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('ice_type_id')->nullable()->after('customer_id')->constrained('ice_types')->onDelete('set null');
            $table->integer('quantity')->default(1)->after('ice_type_id'); // jumlah pcs
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['ice_type_id']);
            $table->dropColumn(['ice_type_id', 'quantity']);
        });
    }
};
