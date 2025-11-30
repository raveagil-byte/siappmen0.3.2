<?php

namespace App\Services;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

class QRService
{
    /**
     * Generate QR code PNG image content for given content string.
     */
    public function generateQRCode(string $content, int $size = 300): string
    {
        return QrCode::format('png')->size($size)->generate($content);
    }

    /**
     * Generate base64 encoded PNG QR code for given content.
     */
    public function generateQRCodeBase64(string $content, int $size = 300): string
    {
        $qrImage = $this->generateQRCode($content, $size);
        return base64_encode($qrImage);
    }

    /**
     * Parse QR code string in format TYPE:UUID, e.g. UNIT:xxx or TRANS:xxx.
     * Returns array with keys 'type' and 'uuid'.
     *
     * @throws \Exception if invalid format.
     */
    public function parseQRCode(string $qrContent): array
    {
        $pattern = '/^(UNIT|TRANS):([0-9a-fA-F-]{36})$/';
        if (preg_match($pattern, $qrContent, $matches)) {
            return [
                'type' => $matches[1],
                'uuid' => $matches[2],
            ];
        }

        throw new \Exception('Invalid QR code format');
    }

    /**
     * Generate Transaction QR content string "TRANS:{uuid}".
     */
    public function generateTransactionQRContent(string $uuid): string
    {
        return "TRANS:".$uuid;
    }

    /**
     * Generate and save QR code to file, return the file path.
     */
    public function generateAndSaveQRCode(string $content, string $filename, int $size = 300): string
    {
        $qrCodeContent = $this->generateQRCode($content, $size);

        // Ensure the qr-codes directory exists
        $directory = 'qr-codes';
        if (!Storage::exists($directory)) {
            Storage::makeDirectory($directory);
        }

        // Save the QR code to file
        $path = $directory . '/' . $filename . '.png';
        Storage::put($path, $qrCodeContent);

        return $path;
    }
}
