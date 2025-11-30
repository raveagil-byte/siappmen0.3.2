<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Disable foreign key checks to allow truncation
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Truncate tables in reverse dependency order to avoid constraint violations
        \App\Models\TransactionItem::truncate();
        \App\Models\ActivityLog::truncate();
        \App\Models\Transaction::truncate();
        \App\Models\InstrumentUnitStatus::truncate();
        \App\Models\Instrument::truncate();
        \App\Models\Unit::truncate();
        \App\Models\User::truncate();

        // Re-enable foreign key checks
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->call([
            UsersTableSeeder::class,
            UnitsSeeder::class,
            InstrumentsSeeder::class,
            InstrumentUnitStatusSeeder::class,
        ]);
    }
}