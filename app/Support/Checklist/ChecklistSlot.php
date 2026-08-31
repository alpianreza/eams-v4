<?php

namespace App\Support\Checklist;

use App\Models\AssetItemType;
use Illuminate\Validation\ValidationException;

/**
 * Normalizes the checklist time-slot identity.
 *
 * BR-14 applies only to the TOILET item type and requires one of PG/SI/SO.
 * Other item types have no slot identity and must persist a null time_slot.
 */
class ChecklistSlot
{
    public const TOILET_CODE = 'TOILET';

    public const PG = 'PG';

    public const SI = 'SI';

    public const SO = 'SO';

    public const TOILET_SLOTS = [self::PG, self::SI, self::SO];

    public static function isRequired(AssetItemType $itemType): bool
    {
        return strtoupper(trim($itemType->code)) === self::TOILET_CODE;
    }

    /**
     * Return the canonical slot used by the persistence identity.
     *
     * The server deliberately ignores slots for non-Toilet item types, so a
     * forged payload cannot create an unintended alternate result set.
     */
    public static function normalize(AssetItemType $itemType, mixed $slot): ?string
    {
        if (! self::isRequired($itemType)) {
            return null;
        }

        $normalized = strtoupper(trim((string) $slot));
        if (! in_array($normalized, self::TOILET_SLOTS, true)) {
            throw ValidationException::withMessages([
                'time_slot' => 'Checklist Toilet wajib memilih slot PG, SI, atau SO.',
            ]);
        }

        return $normalized;
    }
}
