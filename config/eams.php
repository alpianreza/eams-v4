<?php

/*
| EAMS business configuration
*/

return [

    // Saturday holiday effective date (Q-005). NOT retroactive.
    'saturday_holiday_effective' => env('EAMS_SATURDAY_HOLIDAY_EFFECTIVE', '2026-04-01'),

    // Device online threshold, seconds (Q-012). Single centralized value. NOT 48h.
    'device_online_threshold_seconds' => (int) env('EAMS_DEVICE_ONLINE_THRESHOLD_SECONDS', 600),

    // Agent API intervals (technical defaults).
    'agent_heartbeat_interval_seconds' => (int) env('EAMS_AGENT_HEARTBEAT_INTERVAL', 300),
    'agent_command_poll_interval_seconds' => (int) env('EAMS_AGENT_COMMAND_POLL_INTERVAL', 30),

    // Configurable base path for business files (Q-022).
    'files_base_path' => env('EAMS_FILES_BASE_PATH', ''),

    // Logical file storage categories (Q-022).
    'storage_categories' => ['inventory', 'checklist', 'qr', 'attachments'],

    // Public paths a read-only user may still POST to (BR-42 whitelist).
    // Q-021: self-service (settings/*) is whitelisted.
    'write_whitelist' => ['login', 'logout', 'kuesioner', 'kuesioner/*', 'api/agent/*', 'settings', 'settings/*'],

    // Centralized upload validation (Q-026).
    'upload' => [
        'max_kb' => (int) env('EAMS_UPLOAD_MAX_KB', 5120),
        'image_mimes' => ['jpg', 'jpeg', 'png', 'webp'],
    ],

    // Backup (BR-39): retention 30 days; path & disk configurable (Q-022, NOT hard-coded D:\).
    'backup_retention_days' => (int) env('EAMS_BACKUP_RETENTION_DAYS', 30),
    'backup_path' => env('EAMS_BACKUP_PATH', 'backups'),
    'backup_disk' => env('EAMS_BACKUP_DISK', 'local'),

];
