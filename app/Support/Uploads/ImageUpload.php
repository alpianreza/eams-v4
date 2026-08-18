<?php

namespace App\Support\Uploads;

/**
 * Centralized upload validation (Q-026): a single mime+size definition used by all
 * modules — removes the legacy inconsistency where checklist photos had no validation.
 */
class ImageUpload
{
    /** Laravel validation rules for an image upload. */
    public static function rules(): array
    {
        $mimes = implode(',', config('eams.upload.image_mimes', ['jpg', 'jpeg', 'png', 'webp']));
        $maxKb = (int) config('eams.upload.max_kb', 5120);

        return ['image', 'mimes:'.$mimes, 'max:'.$maxKb];
    }
}
