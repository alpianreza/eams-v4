<?php

/*
|--------------------------------------------------------------------------
| EAMS business configuration
|--------------------------------------------------------------------------
*/

return [

    // Saturday holiday effective date (Q-005). NOT retroactive.
    'saturday_holiday_effective' => env('EAMS_SATURDAY_HOLIDAY_EFFECTIVE', '2026-04-01'),

    // Device online threshold, seconds (Q-012). Single centralized value.
    'device_online_threshold_seconds' => (int) env('EAMS_DEVICE_ONLINE_THRESHOLD_SECONDS', 600),

    // Configurable base path for business files (Q-022).
    'files_base_path' => env('EAMS_FILES_BASE_PATH', ''),

    // Logical file storage categories (Q-022).
    'storage_categories' => ['inventory', 'checklist', 'qr', 'attachments'],

    // Public paths a read-only user may still POST to (BR-42 whitelist).
    'write_whitelist' => ['login', 'logout', 'kuesioner', 'kuesioner/*', 'api/agent/*'],

    /*
    | Centralized upload validation (Q-026) — one definition used by every module.
    */
    'upload' => [
        'max_kb' => (int) env('EAMS_UPLOAD_MAX_KB', 5120),
        'image_mimes' => ['jpg', 'jpeg', 'png', 'webp'],
    ],

];
