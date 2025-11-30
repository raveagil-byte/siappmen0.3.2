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
        Schema::table('instrument_unit_status', function (Blueprint $table) {
            // Change status to enum with proper values
            $table->enum('status', ['steril', 'unit', 'kotor', 'cssd'])->default('steril')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instrument_unit_status', function (Blueprint $table) {
            // Revert to string status
            $table->string('status')->default('available')->change();
        });
    }
};
