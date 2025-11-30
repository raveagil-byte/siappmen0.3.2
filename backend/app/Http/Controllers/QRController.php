<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Unit;
use App\Models\Transaction;

class QRController extends Controller
{
    /**
     * Parse a QR code and return the associated data.
     */
    public function parse(Request $request)
    {
        $request->validate([
            'qr_content' => 'required|string',
        ]);

        $content = $request->input('qr_content');

        if (str_starts_with($content, 'UNIT:')) {
            $uuid = str_replace('UNIT:', '', $content);
            $unit = Unit::where('uuid', $uuid)->first();

            if ($unit) {
                return response()->json([
                    'success' => true,
                    'type' => 'unit',
                    'data' => $unit,
                ]);
            }
        } elseif (str_starts_with($content, 'TRANS:')) {
            $uuid = str_replace('TRANS:', '', $content);
            $transaction = Transaction::with('items.instrument', 'unit')->where('uuid', $uuid)->first();

            if ($transaction) {
                return response()->json([
                    'success' => true,
                    'type' => 'transaction',
                    'data' => $transaction,
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid or unrecognized QR code format.',
        ], 404);
    }
}
