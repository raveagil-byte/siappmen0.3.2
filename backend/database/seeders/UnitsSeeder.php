<?php

namespace Database\Seeders;

use App\Models\Unit;
use App\Services\QRService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UnitsSeeder extends Seeder
{
    protected $qrService;

    public function __construct(QRService $qrService)
    {
        $this->qrService = $qrService;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            [
                'code' => 'OK-01',
                'name' => 'Operating Room 1',
                'location' => 'Lantai 3, Gedung A',
                'description' => 'Ruang operasi bedah umum',
            ],
            [
                'code' => 'OK-02',
                'name' => 'Operating Room 2',
                'location' => 'Lantai 3, Gedung A',
                'description' => 'Ruang operasi bedah khusus',
            ],
            [
                'code' => 'ICU-01',
                'name' => 'ICU Unit 1',
                'location' => 'Lantai 4, Gedung A',
                'description' => 'Intensive Care Unit',
            ],
            [
                'code' => 'ICU-02',
                'name' => 'ICU Unit 2',
                'location' => 'Lantai 4, Gedung A',
                'description' => 'Intensive Care Unit',
            ],
            [
                'code' => 'ER-01',
                'name' => 'Emergency Room',
                'location' => 'Lantai 1, Gedung A',
                'description' => 'Unit Gawat Darurat',
            ],
            [
                'code' => 'WARD-01',
                'name' => 'General Ward 1',
                'location' => 'Lantai 2, Gedung B',
                'description' => 'Ruang rawat inap umum',
            ],
        ];

        foreach ($units as $unitData) {
            $unitData['uuid'] = Str::uuid()->toString();
            $unit = Unit::create($unitData);

            // Generate QR code for unit
            $qrContent = "UNIT:{$unit->uuid}";
            $qrPath = $this->qrService->generateAndSaveQRCode($qrContent, "unit_{$unit->code}");
            $unit->update(['qr_code_path' => $qrPath]);
        }
    }
}