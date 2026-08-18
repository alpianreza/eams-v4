<?php

/*
| Configurable file storage (Decision Q-022).
|
| Business files (inventory photos, checklist evidence, QR images, attachments)
| live on category disks whose root is configurable — local path, network share,
| or a custom path (e.g. D:\EAMS\files or \\SERVER-FILE\EAMS). The application
| never depends on an absolute path hard-coded in source.
*/

$eamsBase = (string) env('EAMS_FILES_BASE_PATH', '');

$eamsRoot = static function (string $category) use ($eamsBase): string {
    if ($eamsBase !== '') {
        return rtrim($eamsBase, "/\\").DIRECTORY_SEPARATOR.$category;
    }

    return storage_path('app/'.$category);
};

return [

    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        // --- EAMS business-file categories (Q-022) ---
        'inventory' => [
            'driver' => 'local',
            'root' => $eamsRoot('inventory'),
            'throw' => false,
        ],

        'checklist' => [
            'driver' => 'local',
            'root' => $eamsRoot('checklist'),
            'throw' => false,
        ],

        'qr' => [
            'driver' => 'local',
            'root' => $eamsRoot('qr'),
            'throw' => false,
        ],

        'attachments' => [
            'driver' => 'local',
            'root' => $eamsRoot('attachments'),
            'throw' => false,
        ],

    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
