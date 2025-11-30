<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('instrument_unit_status')) {
            Schema::create('instrument_unit_status', function (Blueprint $table) {
                $table->id();
                $table->foreignId('instrument_id')->constrained()->onDelete('cascade');
                $table->foreignId('unit_id')->constrained()->onDelete('cascade');
                $table->integer('quantity');
                $table->string('status')->default('available');
                $table->timestamps();

                $table->unique(['instrument_id', 'unit_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('instrument_unit_status');
    }
};
