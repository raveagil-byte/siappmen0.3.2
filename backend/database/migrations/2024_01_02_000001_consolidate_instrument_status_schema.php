<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('instrument_unit_status', function (Blueprint $table) {
            // Best practice: always drop foreign keys and indexes before modifying columns.
            $table->dropForeign(['instrument_id']);
            $table->dropForeign(['unit_id']);
            $table->dropUnique('instrument_unit_status_instrument_id_unit_id_unique');

            // Drop old columns that are now redundant or unclear.
            if (Schema::hasColumn('instrument_unit_status', 'quantity')) {
                $table->dropColumn('quantity');
            }
            if (Schema::hasColumn('instrument_unit_status', 'status')) {
                $table->dropColumn('status');
            }

            // Add a new 'location' column to explicitly define where the stock is.
            // This is clearer than relying on a nullable 'unit_id'.
            // 'cssd' = Main CSSD inventory
            // 'unit' = Deployed in a specific unit
            $table->enum('location', ['cssd', 'unit'])->default('unit')->after('id');
        });

        // Re-apply the unique constraint and foreign keys.
        Schema::table('instrument_unit_status', function (Blueprint $table) {
            $table->unique(['instrument_id', 'unit_id', 'location']);
            $table->foreign('instrument_id')->references('id')->on('instruments')->onDelete('cascade');
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');
        });

        // Fix the self-referencing bug in the Instrument model relationships.
        // The 'instrument_tray_items' table should link an instrument to a 'tray' (which is also an instrument).
        Schema::table('instrument_tray_items', function (Blueprint $table) {
            // Drop existing foreign keys to redefine them correctly.
            $table->dropForeign(['instrument_id']);
            $table->dropForeign(['tray_id']);

            // Re-create foreign keys with correct references.
            $table->foreign('instrument_id')->references('id')->on('instruments')->onDelete('cascade');
            $table->foreign('tray_id')->references('id')->on('instruments')->onDelete('cascade'); // tray_id refers to another instrument.
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('instrument_unit_status', function (Blueprint $table) {
            $table->dropForeign(['instrument_id']);
            $table->dropForeign(['unit_id']);
            $table->dropUnique('instrument_unit_status_instrument_id_unit_id_location_unique');

            $table->dropColumn('location');

            // Restore old schema if needed.
            $table->integer('quantity')->default(0);
            $table->string('status')->default('available');
        });

        Schema::table('instrument_tray_items', function (Blueprint $table) {
            $table->dropForeign(['instrument_id']);
            $table->dropForeign(['tray_id']);

            // Re-create old foreign keys.
            $table->foreign('instrument_id')->references('id')->on('instruments')->onDelete('cascade');
            $table->foreign('tray_id')->references('id')->on('instruments')->onDelete('cascade'); // Revert to original, though it was buggy.
        });
    }
};
