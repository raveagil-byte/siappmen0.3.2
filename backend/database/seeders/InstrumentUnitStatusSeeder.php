<?php

namespace Database\Seeders;

use App\Models\Instrument;
use App\Models\InstrumentUnitStatus;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class InstrumentUnitStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $instruments = Instrument::all();
        $units = Unit::all();

        foreach ($instruments as $instrument) {
            foreach ($units as $unit) {
                InstrumentUnitStatus::create([
                    'instrument_id' => $instrument->id,
                    'unit_id' => $unit->id,
                    'quantity' => rand(0, 10),
                    'status' => 'available',
                ]);
            }
        }
    }
}
