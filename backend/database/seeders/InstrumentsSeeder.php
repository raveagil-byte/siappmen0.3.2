<?php

namespace Database\Seeders;

use App\Models\Instrument;
use Illuminate\Database\Seeder;

class InstrumentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Single instruments
        $instruments = [
            [
                'code' => 'SCALPEL-001',
                'name' => 'Scalpel Blade #10',
                'type' => 'single',
                'category' => 'Surgical',
                'description' => 'Surgical scalpel blade size 10',
                'manufacturer' => 'Medical Corp',
                'reusable_count' => 50,
            ],
            [
                'code' => 'SCALPEL-002',
                'name' => 'Scalpel Blade #15',
                'type' => 'single',
                'category' => 'Surgical',
                'description' => 'Surgical scalpel blade size 15',
                'manufacturer' => 'Medical Corp',
                'reusable_count' => 50,
            ],
            [
                'code' => 'FORCEPS-001',
                'name' => 'Tissue Forceps',
                'type' => 'single',
                'category' => 'Surgical',
                'description' => 'Standard tissue forceps',
                'manufacturer' => 'SurgiTech',
                'reusable_count' => 100,
            ],
            [
                'code' => 'FORCEPS-002',
                'name' => 'Hemostatic Forceps',
                'type' => 'single',
                'category' => 'Surgical',
                'description' => 'Hemostatic forceps for clamping',
                'manufacturer' => 'SurgiTech',
                'reusable_count' => 100,
            ],
            [
                'code' => 'SCISSORS-001',
                'name' => 'Mayo Scissors Straight',
                'type' => 'single',
                'category' => 'Surgical',
                'description' => 'Mayo scissors straight blade',
                'manufacturer' => 'SurgiTech',
                'reusable_count' => 80,
            ],
            [
                'code' => 'SCISSORS-002',
                'name' => 'Mayo Scissors Curved',
                'type' => 'single',
                'category' => 'Surgical',
                'description' => 'Mayo scissors curved blade',
                'manufacturer' => 'SurgiTech',
                'reusable_count' => 80,
            ],
            [
                'code' => 'NEEDLE-001',
                'name' => 'Needle Holder',
                'type' => 'single',
                'category' => 'Surgical',
                'description' => 'Standard needle holder',
                'manufacturer' => 'Medical Corp',
                'reusable_count' => 100,
            ],
            [
                'code' => 'RETRACTOR-001',
                'name' => 'Skin Retractor',
                'type' => 'single',
                'category' => 'Surgical',
                'description' => 'Skin retractor small',
                'manufacturer' => 'SurgiTech',
                'reusable_count' => 100,
            ],
            [
                'code' => 'CLAMP-001',
                'name' => 'Towel Clamp',
                'type' => 'single',
                'category' => 'Surgical',
                'description' => 'Towel clamp for draping',
                'manufacturer' => 'Medical Corp',
                'reusable_count' => 100,
            ],
            [
                'code' => 'SUCTION-001',
                'name' => 'Suction Tube',
                'type' => 'single',
                'category' => 'Surgical',
                'description' => 'Yankauer suction tube',
                'manufacturer' => 'Medical Corp',
                'reusable_count' => 50,
            ],
        ];

        foreach ($instruments as $instrumentData) {
            Instrument::create($instrumentData);
        }

        // Create a tray/set
        $tray = Instrument::create([
            'code' => 'TRAY-BASIC-001',
            'name' => 'Basic Surgical Tray',
            'type' => 'tray',
            'category' => 'Surgical',
            'description' => 'Basic surgical instrument tray',
            'manufacturer' => 'Hospital CSSD',
            'reusable_count' => 100,
        ]);

        // Attach instruments to tray
        $trayInstruments = [
            'SCALPEL-001' => 2,
            'FORCEPS-001' => 2,
            'SCISSORS-001' => 1,
            'NEEDLE-001' => 1,
            'CLAMP-001' => 4,
        ];

        foreach ($trayInstruments as $code => $quantity) {
            $instrument = Instrument::where('code', $code)->first();
            if ($instrument) {
                $tray->trayInstruments()->attach($instrument->id, ['quantity' => $quantity]);
            }
        }
    }
}