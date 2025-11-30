<?php

use Illuminate_Database_Migrations_Migration;
use Illuminate_Database_Schema_Blueprint;
use Illuminate_Support_Facades_Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('instruments', function (Blueprint $table) {
            $table->boolean('is_tray')->default(false)->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instruments', function (Blueprint $table) {
            $table->dropColumn('is_tray');
        });
    }
};
