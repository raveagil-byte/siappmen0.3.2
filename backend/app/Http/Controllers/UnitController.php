<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;

class UnitController extends Controller
{
    /**
     * Display a listing of units.
     */
    public function index()
    {
        $units = Unit::all();
        return response()->json([
            'success' => true,
            'data' => $units,
        ]);
    }

    /**
     * Store a newly created unit in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:units,name',
            'description' => 'nullable|string',
        ]);

        $unit = new Unit();
        $unit->name = $request->name;
        $unit->description = $request->description;
        $unit->uuid = (string) Str::uuid();
        $unit->save();

        return response()->json([
            'success' => true,
            'message' => 'Unit created successfully',
            'data' => $unit,
        ], 201);
    }

    /**
     * Display the specified unit.
     */
    public function show(Unit $unit)
    {
        return response()->json([
            'success' => true,
            'data' => $unit,
        ]);
    }

    /**
     * Update the specified unit in storage.
     */
    public function update(Request $request, Unit $unit)
    {
        $request->validate([
            'name' => 'required|string|unique:units,name,'.$unit->id,
            'description' => 'nullable|string',
        ]);

        $unit->name = $request->name;
        $unit->description = $request->description;
        $unit->save();

        return response()->json([
            'success' => true,
            'message' => 'Unit updated successfully',
            'data' => $unit,
        ]);
    }

    /**
     * Remove the specified unit from storage.
     */
    public function destroy(Unit $unit)
    {
        $unit->delete();
        return response()->json([
            'success' => true,
            'message' => 'Unit deleted successfully',
        ]);
    }

    /**
     * Get QR code image base64 for the unit.
     */
    public function getQRCode(Unit $unit)
    {
        $qrContent = "UNIT:".$unit->uuid;
        $qrImage = QrCode::format('png')->size(300)->generate($qrContent);
        $base64 = base64_encode($qrImage);

        return response()->json([
            'success' => true,
            'data' => [
                'qr_content' => $qrContent,
                'qr_base64' => $base64,
            ],
        ]);
    }

    /**
     * Regenerate the UUID and QR code for a unit.
     */
    public function regenerateQRCode(Unit $unit)
    {
        $unit->uuid = (string) Str::uuid();
        $unit->save();

        $qrContent = "UNIT:".$unit->uuid;
        $qrImage = QrCode::format('png')->size(300)->generate($qrContent);
        $base64 = base64_encode($qrImage);

        return response()->json([
            'success' => true,
            'message' => 'QR code regenerated successfully',
            'data' => [
                'qr_content' => $qrContent,
                'qr_base64' => $base64,
            ],
        ]);
    }
}
