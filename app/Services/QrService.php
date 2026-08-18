<?php

namespace App\Services;

use App\Models\ComplianceInventory;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;

/**
 * QR compatibility (Q-021 / BR-20): the QR payload/URL is IDENTICAL to legacy
 * (`compliance/inventory/detail/{id}`); only the image is regenerated locally
 * (removing the legacy external api.qrserver.com dependency). Asset code unchanged.
 * Stored on the configurable `qr` disk (Q-022).
 */
class QrService
{
    /** The legacy-identical QR payload (Q-021). */
    public function detailUrl(ComplianceInventory $inventory): string
    {
        return url('compliance/inventory/detail/'.$inventory->id);
    }

    public function relativePath(ComplianceInventory $inventory): string
    {
        return $inventory->id.'.png';
    }

    public function generate(ComplianceInventory $inventory): string
    {
        $qrCode = new QrCode(
            data: $this->detailUrl($inventory),
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255),
        );

        $result = (new PngWriter())->write($qrCode);

        $path = $this->relativePath($inventory);
        Storage::disk('qr')->put($path, $result->getString());

        return $path;
    }
}
