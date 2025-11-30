<?php

namespace App\Http\Controllers;

use App\Services\QRService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class QRController extends Controller
{
    protected $qrService;

    public function __construct(QRService $qrService)
    {
        $this->qrService = $qrService;
    }

    /**
     * Parse QR code content string
     */
    public function parse(Request $request)
    {
        $request->validate([
            'qr_content' => 'required|string',
        ]);

        try {
            $result = $this->qrService->parseQRCode($request->qr_content);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Generate QR code image for given content (GET /api/qr/generate?content=TEXT)
     */
    public function generate(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
            'size' => 'nullable|integer|min:100|max:1000',
        ]);

        $size = $request->get('size', 300);
        $content = $request->get('content');

        $qrImage = $this->qrService->generateQRCode($content, $size);

        return Response::make($qrImage, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="qrcode.png"',
        ]);
    }
}
