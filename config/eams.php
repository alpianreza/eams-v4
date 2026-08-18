<?php

/*
|--------------------------------------------------------------------------
| EAMS business configuration
|--------------------------------------------------------------------------
| Central, configurable values mandated by the approved Human Decisions.
| Nothing here is hard-coded into controllers/helpers — always read via
| config('eams.*').
*/

return [

    /*
    | Saturday holiday effective date (Decision Q-005).
    | Before this date Saturday = working day; from this date Saturday = holiday.
    | NOT retroactive — history stays consistent with the policy at that time.
    */
    'saturday_holiday_effective' => env('EAMS_SATURDAY_HOLIDAY_EFFECTIVE', '2026-04-01'),

    /*
    | Device online threshold, in seconds (Decision Q-012).
    | Single centralized value shared by UI, helper and status checker.
    | last_seen <= threshold  => online;  otherwise => offline.
    */
    'device_online_threshold_seconds' => (int) env('EAMS_DEVICE_ONLINE_THRESHOLD_SECONDS', 600),

    /*
    | Configurable base path for business files (Decision Q-022).
    | Empty => default to storage/app/<category>.
    */
    'files_base_path' => env('EAMS_FILES_BASE_PATH', ''),

    /*
    | Logical file storage categories (Decision Q-022).
    */
    'storage_categories' => ['inventory', 'checklist', 'qr', 'attachments'],

];
