<?php

/*
| Sidebar menu catalog (BR-44): grouped, filtered by page_access (admin sees all).
| Each item: label, route (+ optional params), icon (Bootstrap Icons), page key (access).
*/

return [
    ['group' => 'Utama', 'items' => [
        ['label' => 'Beranda', 'route' => 'home', 'icon' => 'house-door', 'page' => 'home'],
        ['label' => 'Notifikasi', 'route' => 'notifications.index', 'icon' => 'bell', 'page' => 'notifications'],
        ['label' => 'Pengaturan', 'route' => 'settings.index', 'icon' => 'gear', 'page' => 'settings'],
    ]],

    ['group' => 'Compliance', 'items' => [
        ['label' => 'Dashboard', 'route' => 'dashboard.index', 'icon' => 'speedometer2', 'page' => 'dashboard'],
        ['label' => 'Inventory', 'route' => 'compliance.inventory.index', 'icon' => 'box-seam', 'page' => 'compliance_inventory'],
        ['label' => 'Progress', 'route' => 'progress.index', 'icon' => 'bar-chart-line', 'page' => 'progress'],
        ['label' => 'Evidence', 'route' => 'evidence.index', 'icon' => 'exclamation-triangle', 'page' => 'evidence'],
        ['label' => 'Ranking', 'route' => 'ranking.index', 'icon' => 'trophy', 'page' => 'ranking'],
        ['label' => 'Kalender', 'route' => 'calendar.index', 'icon' => 'calendar3', 'page' => 'calendar'],
        ['label' => 'Kuesioner', 'route' => 'questionnaire.index', 'icon' => 'ui-checks', 'page' => 'questionnaires'],
        ['label' => 'Thermal Imaging', 'route' => 'thermal.index', 'icon' => 'thermometer-half', 'page' => 'thermal_imaging'],
        ['label' => 'Print Center', 'route' => 'print.index', 'icon' => 'printer', 'page' => 'print_center'],
    ]],

    ['group' => 'Boiler & Utility', 'items' => [
        ['label' => 'Boiler', 'route' => 'utility.index', 'params' => ['type' => 'boiler'], 'icon' => 'fire', 'page' => 'boiler_fuel'],
        ['label' => 'PDAM Water', 'route' => 'utility.index', 'params' => ['type' => 'pdam-water'], 'icon' => 'droplet', 'page' => 'pdam_water'],
        ['label' => 'PDAM Boiler', 'route' => 'utility.index', 'params' => ['type' => 'pdam-water-boiler'], 'icon' => 'droplet-half', 'page' => 'pdam_water_boiler'],
        ['label' => 'IPAL', 'route' => 'utility.index', 'params' => ['type' => 'ipal'], 'icon' => 'recycle', 'page' => 'ipal'],
    ]],

    ['group' => 'EMS / GHG', 'items' => [
        ['label' => 'Konsumsi Air', 'route' => 'ems.index', 'params' => ['category' => 'water'], 'icon' => 'droplet', 'page' => 'ems_reports'],
        ['label' => 'Konsumsi Listrik', 'route' => 'ems.index', 'params' => ['category' => 'electric'], 'icon' => 'lightning-charge', 'page' => 'ems_reports'],
        ['label' => 'Stationary Combustion', 'route' => 'ems.index', 'params' => ['category' => 'stationary'], 'icon' => 'gear', 'page' => 'ems_reports'],
        ['label' => 'Mobile Combustion', 'route' => 'ems.index', 'params' => ['category' => 'mobile'], 'icon' => 'truck', 'page' => 'ems_reports'],
    ]],

    ['group' => 'Security', 'items' => [
        ['label' => 'Patrol', 'route' => 'patrol.index', 'icon' => 'shield-check', 'page' => 'patrol_daily'],
    ]],

    ['group' => 'IT', 'items' => [
        ['label' => 'Devices', 'route' => 'it.devices.index', 'icon' => 'pc-display', 'page' => 'it_devices'],
        ['label' => 'IT Assets', 'route' => 'it-assets.index', 'icon' => 'laptop', 'page' => 'it_assets'],
        ['label' => 'FDM', 'route' => 'fdm.index', 'icon' => 'collection', 'page' => 'fdm_data_collection'],
    ]],

    ['group' => 'Master Data', 'items' => [
        ['label' => 'Areas', 'route' => 'master-data.areas.index', 'icon' => 'geo-alt', 'page' => 'master_areas'],
        ['label' => 'Kategori', 'route' => 'master-data.categories.index', 'icon' => 'tags', 'page' => 'master_categories'],
        ['label' => 'Item Types', 'route' => 'master-data.item-types.index', 'icon' => 'list-check', 'page' => 'master_item_types'],
        ['label' => 'Checklist Master', 'route' => 'checklist-master.index', 'icon' => 'clipboard-check', 'page' => 'checklist_master'],
        ['label' => 'Hari Libur', 'route' => 'master-data.holidays.index', 'icon' => 'calendar-x', 'page' => 'master_holidays'],
        ['label' => 'Karyawan', 'route' => 'master-data.employees.index', 'icon' => 'people', 'page' => 'master_employees'],
    ]],

    ['group' => 'Admin', 'items' => [
        ['label' => 'Users', 'route' => 'users.index', 'icon' => 'people', 'page' => 'users_management'],
        ['label' => 'Audit Logs', 'route' => 'admin.audit-logs.index', 'icon' => 'journal-text', 'page' => 'admin_audit'],
        ['label' => 'Login Sessions', 'route' => 'admin.login-sessions.index', 'icon' => 'person-lines-fill', 'page' => 'admin_sessions'],
        ['label' => 'Backups', 'route' => 'admin.backups.index', 'icon' => 'cloud-arrow-down', 'page' => 'admin_backups'],
    ]],
];
