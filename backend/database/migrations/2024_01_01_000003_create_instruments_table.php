<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instruments', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('type'); // ADD THIS
            $table->string('category')->nullable(); // ADD THIS
            $table->text('description')->nullable();
            $table->string('manufacturer')->nullable(); // ADD THIS
            $table->integer('reusable_count')->default(1); // ADD THIS
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instruments');
    }
};
