<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class TransactionPhotoController extends Controller
{
    /**
     * Get photos for a transaction
     */
    public function index(Transaction $transaction)
    {
        $photos = $transaction->photos()->with('uploader')->get();

        return response()->json([
            'success' => true,
            'data' => $photos,
        ]);
    }

    /**
     * Upload photo for a transaction
     */
    public function upload(Request $request, Transaction $transaction)
    {
        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'photo_type' => 'required|in:before,after,verification',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Store the file
            $path = $request->file('photo')->store('transaction-photos', 'public');

            // Create photo record
            $photo = TransactionPhoto::create([
                'transaction_id' => $transaction->id,
                'photo_path' => $path,
                'photo_type' => $request->photo_type,
                'uploaded_by' => $request->user()->id,
                'notes' => $request->notes,
            ]);

            $photo->load('uploader');

            return response()->json([
                'success' => true,
                'message' => 'Photo uploaded successfully',
                'data' => $photo,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload photo: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a transaction photo
     */
    public function destroy(Transaction $transaction, TransactionPhoto $photo)
    {
        // Ensure the photo belongs to the transaction
        if ($photo->transaction_id !== $transaction->id) {
            return response()->json([
                'success' => false,
                'message' => 'Photo does not belong to this transaction',
            ], 403);
        }

        try {
            // Delete the file
            Storage::disk('public')->delete($photo->photo_path);

            // Delete the record
            $photo->delete();

            return response()->json([
                'success' => true,
                'message' => 'Photo deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete photo: ' . $e->getMessage(),
            ], 500);
        }
    }
}
