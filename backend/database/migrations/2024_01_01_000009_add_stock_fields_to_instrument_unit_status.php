<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instrument_unit_status', function (Blueprint $table) {

            // 1. DROP FOREIGN KEYS dulu
            $table->dropForeign(['instrument_id']);
            $table->dropForeign(['unit_id']);

            // 2. DROP UNIQUE INDEX lama
            $table->dropUnique('instrument_unit_status_instrument_id_unit_id_unique');

            // 3. Tambah kolom baru
            $table->integer('stock_steril')->default(0)->after('quantity');
            $table->integer('stock_kotor')->default(0)->after('stock_steril');
            $table->integer('stock_in_use')->default(0)->after('stock_kotor');

            // 4. ubah unit_id menjadi nullable, TAPI tetap BIGINT
            $table->unsignedBigInteger('unit_id')->nullable()->change();

            // 5. Tambah unique baru
            $table->unique(['instrument_id', 'unit_id']);

            // 6. Tambah foreign key lagi
            $table->foreign('instrument_id')->references('id')->on('instruments')->onDelete('cascade');
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('instrument_unit_status', function (Blueprint $table) {
            // Drop unique constraint
            $table->dropUnique(['instrument_id', 'unit_id']);

            // Drop added stock fields
            $table->dropColumn(['stock_steril', 'stock_kotor', 'stock_in_use']);

            // Change back to NOT NULL
            $table->unsignedBigInteger('unit_id')->nullable(false)->change();

            // Restore original unique constraint
            $table->unique(['instrument_id', 'unit_id']);
        });
    }
};
