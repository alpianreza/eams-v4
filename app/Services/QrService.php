<?php

namespace App\Services;

use App\Models\ComplianceInventory;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;

/**
 * QR compatibility (Q-021 / BR-20): the QR payload/URL is IDENTICAL to legacy
 * (`compliance/inventory/detail/{id}`); only the image is regenerated locally
 * (removing the legacy external api.qrserver.com dependency). Stored on the
 * configurable `qr` disk (Q-022).
 *
 * Uses endroid/qr-code v5 (PHP 8.2-compatible) via the minimal Builder API +
 * PngWriter (GD) — avoids the v6-only enum/named-arg API that requires PHP 8.4.
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
        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($this->detailUrl($inventory))
            ->size(300)
            ->margin(10)
            ->build();

        $path = $this->relativePath($inventory);
        Storage::disk('qr')->put($path, $result->getString());

        return $path;
    }
}
