<?php

namespace App\Actions\Compliance;

use App\Models\AssetItemType;
use App\Models\ComplianceInventory;
use App\Models\InventoryCategory;

/**
 * Asset code generator (BR-19) — exact legacy format, verified from CI4 source:
 *   strtoupper(category.code) . '-' . strtoupper(item.code) . '-' . 3-digit sequence
 * The sequence increments from the largest trailing number for that prefix.
 *
 * Q-020: legacy codes are preserved exactly (never regenerated); only NEW assets
 * use this generator. Duplicates are rejected by the unique rule (never auto-renamed).
 */
class GenerateAssetCode
{
    public static function generate(InventoryCategory $category, AssetItemType $itemType): string
    {
        $prefix = strtoupper((string) $category->code).'-'.strtoupper((string) $itemType->code);

        $last = ComplianceInventory::query()
            ->where('asset_code', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->first();

        $next = 1;
        if ($last && preg_match('/(\d+)\s*$/', $last->asset_code, $m)) {
            $next = ((int) $m[1]) + 1;
        }

        return $prefix.'-'.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }
}
