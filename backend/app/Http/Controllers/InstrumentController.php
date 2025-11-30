<?php

namespace App\Http\Controllers;

use App\Models\Instrument;
use Illuminate\Http\Request;

class InstrumentController extends Controller
{
    /**
     * Display a listing of instruments.
     */
    public function index()
    {
        $instruments = Instrument::all();
        return response()->json([
            'success' => true,
            'data' => $instruments,
        ]);
    }

    /**
     * Store a newly created instrument in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:instruments,name',
            'code' => 'required|string|unique:instruments,code',
            'description' => 'nullable|string',
        ]);

        $instrument = Instrument::create([
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Instrument created successfully',
            'data' => $instrument,
        ], 201);
    }

    /**
     * Display the specified instrument.
     */
    public function show(Instrument $instrument)
    {
        return response()->json([
            'success' => true,
            'data' => $instrument,
        ]);
    }

    /**
     * Update the specified instrument in storage.
     */
    public function update(Request $request, Instrument $instrument)
    {
        $request->validate([
            'name' => 'required|string|unique:instruments,name,'.$instrument->id,
            'code' => 'required|string|unique:instruments,code,'.$instrument->id,
            'description' => 'nullable|string',
        ]);

        $instrument->name = $request->name;
        $instrument->code = $request->code;
        $instrument->description = $request->description;
        $instrument->save();

        return response()->json([
            'success' => true,
            'message' => 'Instrument updated successfully',
            'data' => $instrument,
        ]);
    }

    /**
     * Remove the specified instrument from storage.
     */
    public function destroy(Instrument $instrument)
    {
        $instrument->delete();
        return response()->json([
            'success' => true,
            'message' => 'Instrument deleted successfully',
        ]);
    }
}
