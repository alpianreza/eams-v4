<?php

namespace App\Services\Import;

use App\Models\Area;
use App\Models\AssetItemType;
use App\Models\ChecklistMaster;
use App\Models\ComplianceInventory;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\InventoryCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Legacy CI4 → Laravel data import (2L).
 *
 * The source connection is read-only. Target writes run in one transaction:
 * dry-runs and imports containing validation errors are rolled back completely.
 * Checklist logs use legacy_id plus a business-key fallback so reruns update the
 * same rows without deleting application-created logs or audit histories.
 */
class LegacyImporter
{
    public array $report = [];

    public bool $rolledBack = false;

    /** @var array<string, array<int|string, int>> legacy-id → new-id per table */
    protected array $map = [];

    public function __construct(protected bool $dryRun = false) {}

    public function run(): array
    {
        $target = DB::connection();
        $target->beginTransaction();

        try {
            $this->importUsers();
            $this->importAreas();
            $this->importCategories();
            $this->importItemTypes();
            $this->importHolidays();
            $this->importEmployees();
            $this->importInventories();
            $this->importInventoryPics();
            $this->importChecklistMaster();
            $this->importChecklistLogs();

            $errorCount = array_sum(array_map(
                fn (array $result): int => count($result['errors'] ?? []),
                $this->report
            ));

            if ($this->dryRun || $errorCount > 0) {
                $target->rollBack();
                $this->rolledBack = true;
            } else {
                $target->commit();
            }

            return $this->report;
        } catch (\Throwable $e) {
            if ($target->transactionLevel() > 0) {
                $target->rollBack();
            }
            $this->rolledBack = true;

            throw $e;
        }
    }

    protected function importUsers(): void
    {
        foreach ($this->rows('users', 'users') as $row) {
            $this->write('users', function () use ($row) {
                $username = trim((string) ($row->username ?? ''));
                if ($username === '') {
                    throw new \RuntimeException("user#{$row->id}: username kosong");
                }

                // Query builder preserves the original bcrypt hash exactly.
                DB::table('users')->upsert([
                    'username' => $username,
                    'name' => $row->name ?? $username,
                    'email' => $row->email ?: null,
                    'password' => (string) ($row->password ?? ''),
                    'photo' => $row->photo ?? null,
                    'role' => $this->mapRole((string) ($row->role ?? 'staff')),
                    'permission' => in_array($row->permission ?? 'read', ['read', 'write'], true) ? $row->permission : 'read',
                    'page_access' => $this->toJsonOrNull($row->page_access ?? null),
                    'status' => ($row->status ?? 'active') === 'active' ? 'active' : 'inactive',
                    'wa_number' => $row->wa_number ?? null,
                    'created_at' => $this->toDateTime($row->created_at ?? null) ?? now(),
                    'updated_at' => now(),
                ], ['username'], ['name', 'email', 'password', 'photo', 'role', 'permission', 'page_access', 'status', 'wa_number', 'updated_at']);

                $userId = DB::table('users')->where('username', $username)->value('id');
                $this->mapId('users', $row->id ?? 0, $userId);
            });
        }
    }

    protected function importAreas(): void
    {
        foreach ($this->rows('areas', 'areas') as $row) {
            $this->write('areas', function () use ($row) {
                $area = Area::withoutEvents(fn () => Area::updateOrCreate(
                    ['name' => (string) $row->name],
                    ['active' => (bool) ($row->active ?? true)]
                ));
                $this->mapId('areas', $row->id ?? 0, $area->id);
            });
        }
    }

    protected function importCategories(): void
    {
        foreach ($this->rows('inventory_categories', 'inventory_categories') as $row) {
            $this->write('inventory_categories', function () use ($row) {
                $cat = InventoryCategory::withoutEvents(fn () => InventoryCategory::updateOrCreate(
                    ['name' => (string) $row->name],
                    [
                        'code' => $row->code ?? strtoupper(substr((string) $row->name, 0, 3)),
                        'active' => (bool) ($row->active ?? true),
                    ]
                ));
                $this->mapId('inventory_categories', $row->id ?? 0, $cat->id);
            });
        }
    }

    protected function importItemTypes(): void
    {
        foreach ($this->rows('asset_item_types', 'asset_item_types') as $row) {
            $this->write('asset_item_types', function () use ($row) {
                $legacyCategoryId = $row->inventory_category_id ?? $row->category_id ?? 0;
                $categoryId = $this->mapped('inventory_categories', $legacyCategoryId);
                if (! $categoryId) {
                    throw new \RuntimeException("item-type#{$row->id}: kategori {$legacyCategoryId} tidak ter-resolve");
                }

                $itemType = AssetItemType::withoutEvents(fn () => AssetItemType::updateOrCreate(
                    ['code' => (string) $row->code],
                    [
                        'inventory_category_id' => $categoryId,
                        'name' => $row->name ?? $row->code,
                        'checklist_frequency' => in_array($row->checklist_frequency ?? '', ['daily', 'weekly', 'monthly'], true)
                            ? $row->checklist_frequency
                            : 'monthly',
                        'allow_na' => (bool) ($row->allow_na ?? false),
                        'active' => (bool) ($row->active ?? true),
                    ]
                ));
                $this->mapId('asset_item_types', $row->id ?? 0, $itemType->id);
            });
        }
    }

    protected function importHolidays(): void
    {
        foreach ($this->rows('holidays', 'holidays') as $row) {
            $this->write('holidays', function () use ($row) {
                $date = $this->toDate($row->holiday_date ?? null);
                if (! $date) {
                    throw new \RuntimeException("holiday#{$row->id}: tanggal tidak valid");
                }

                Holiday::withoutEvents(fn () => Holiday::updateOrCreate(
                    ['holiday_date' => $date],
                    ['description' => $row->description ?? null]
                ));
            });
        }
    }

    protected function importEmployees(): void
    {
        foreach ($this->rows('employees', 'employees') as $row) {
            $this->write('employees', function () use ($row) {
                Employee::withoutEvents(fn () => Employee::updateOrCreate(
                    ['employee_id' => (string) $row->employee_id],
                    [
                        'name' => $row->name ?? $row->employee_id,
                        'division' => (string) ($row->division ?? ''),
                        'position' => (string) ($row->position ?? ''),
                        'photo' => $row->photo ?? null,
                        'status' => ($row->status ?? 'active') === 'inactive' ? 'inactive' : 'active',
                    ]
                ));
            });
        }
    }

    protected function importInventories(): void
    {
        foreach ($this->rows('compliance_inventories', 'compliance_inventory') as $row) {
            $this->write('compliance_inventories', function () use ($row) {
                $categoryId = $this->mapped('inventory_categories', $row->category_id ?? 0);
                $itemTypeId = $this->mapped('asset_item_types', $row->item_type_id ?? 0);
                $areaId = $this->mapped('areas', $row->area_id ?? 0);

                if (! $categoryId || ! $itemTypeId) {
                    throw new \RuntimeException("asset_code {$row->asset_code}: kategori/item-type tidak ter-resolve");
                }

                $inv = ComplianceInventory::withoutEvents(fn () => ComplianceInventory::updateOrCreate(
                    ['asset_code' => (string) $row->asset_code],
                    [
                        'inventory_category_id' => $categoryId,
                        'asset_item_type_id' => $itemTypeId,
                        'area_id' => $areaId,
                        'type_description' => $row->type_description ?? null,
                        'specific_area' => $row->specific_area ?? null,
                        'pic' => $row->pic ?? null,
                        'status' => $this->mapStatus((string) ($row->status ?? '')),
                        'qty' => max(0, (int) ($row->qty ?? 1)),
                        'remark' => $row->remark ?? null,
                        'expired_date' => $this->toDate($row->expired_date ?? null),
                        'photo' => $row->photo ?? null,
                        'qr_image' => $row->qr_image ?? null,
                        'active' => (bool) ($row->active ?? true),
                    ]
                ));
                $this->mapId('compliance_inventories', $row->id ?? 0, $inv->id);
            });
        }
    }

    protected function importInventoryPics(): void
    {
        foreach ($this->rows('compliance_inventory_pics', 'compliance_inventory') as $row) {
            $this->write('compliance_inventory_pics', function () use ($row) {
                $invId = $this->mapped('compliance_inventories', $row->id ?? 0);
                $inventory = $invId
                    ? ComplianceInventory::find($invId)
                    : ComplianceInventory::where('asset_code', (string) ($row->asset_code ?? ''))->first();
                if (! $inventory) {
                    throw new \RuntimeException("inventory#{$row->id}: PIC tidak dapat dipetakan");
                }

                $names = array_filter(array_map('trim', preg_split('/[-,\n]/', (string) ($row->pic ?? ''))));
                $userIds = [];
                foreach (array_slice($names, 0, 2) as $name) {
                    $user = User::where('name', $name)->first();
                    if ($user) {
                        $userIds[] = $user->id;
                    }
                }
                $inventory->pics()->sync(array_values(array_unique($userIds)));
            });
        }
    }

    protected function importChecklistMaster(): void
    {
        foreach ($this->rows('checklist_master', 'checklist_master') as $row) {
            $this->write('checklist_master', function () use ($row) {
                $itemTypeId = $this->mapped('asset_item_types', $row->item_type_id ?? 0);
                if (! $itemTypeId) {
                    throw new \RuntimeException("question#{$row->id}: item_type tidak ter-resolve");
                }

                $frequency = in_array($row->frequency ?? '', ['daily', 'weekly', 'monthly'], true)
                    ? $row->frequency
                    : (AssetItemType::whereKey($itemTypeId)->value('checklist_frequency') ?? 'monthly');

                $question = ChecklistMaster::withoutEvents(fn () => ChecklistMaster::updateOrCreate(
                    ['asset_item_type_id' => $itemTypeId, 'question' => (string) $row->question],
                    [
                        'frequency' => $frequency,
                        'require_photo' => (bool) ($row->require_photo ?? false),
                        'active' => (bool) ($row->active ?? true),
                    ]
                ));
                $this->mapId('checklist_master', $row->id ?? 0, $question->id);
            });
        }
    }

    protected function importChecklistLogs(): void
    {
        $legacy = DB::connection('legacy');
        if (! Schema::connection('legacy')->hasTable('checklist_logs')) {
            $this->report['checklist_logs'] = ['read' => 0, 'written' => 0, 'errors' => [], 'skipped' => true];

            return;
        }

        if (! Schema::hasColumn('checklist_logs', 'legacy_id')) {
            $this->report['checklist_logs'] = [
                'read' => 0,
                'written' => 0,
                'errors' => ['Kolom checklist_logs.legacy_id belum tersedia. Jalankan php artisan migrate.'],
            ];

            return;
        }

        $usersByName = User::query()->get(['id', 'name'])
            ->mapWithKeys(fn (User $user): array => [strtolower(trim($user->name)) => $user->id])
            ->all();
        $inventoryTypeById = ComplianceInventory::pluck('asset_item_type_id', 'id')->all();

        $rows = [];
        $read = 0;
        $written = 0;
        $errors = [];

        foreach ($legacy->table('checklist_logs')->orderBy('id')->cursor() as $row) {
            $read++;
            $legacyId = (int) ($row->id ?? 0);
            $invId = $this->mapped('compliance_inventories', $row->inventory_id ?? 0);
            $questionId = $this->mapped('checklist_master', $row->checklist_template_id ?? 0);
            $itemTypeId = $invId ? ($inventoryTypeById[$invId] ?? null) : null;

            if ($legacyId <= 0 || ! $invId || ! $questionId || ! $itemTypeId) {
                $errors[] = "log#{$legacyId}: inventory/question/item-type tidak ter-resolve (inv_id=".
                    ($row->inventory_id ?? '').', template_id='.($row->checklist_template_id ?? '').')';
                continue;
            }

            $checkerName = trim((string) ($row->checked_by ?? ''));
            $checkerId = $checkerName !== '' ? ($usersByName[strtolower($checkerName)] ?? null) : null;
            $checkDate = $this->toDate($row->check_date ?? null)
                ?? $this->periodKeyToDate((string) ($row->period_key ?? ''));

            if (! $checkDate) {
                $errors[] = "log#{$legacyId}: check_date tidak valid (period_key=".($row->period_key ?? '').')';
                continue;
            }

            $rows[] = [
                'legacy_id' => $legacyId,
                'inventory_id' => $invId,
                'asset_item_type_id' => $itemTypeId,
                'checklist_master_id' => $questionId,
                'check_date' => $checkDate,
                'period_key' => (string) ($row->period_key ?? ''),
                'time_slot' => $row->time_slot ?? null,
                'status' => $this->mapChecklistStatus($row->status ?? null),
                'remark' => $row->remark ?? null,
                'photo' => $row->photo ?? null,
                'checked_by_user_id' => $checkerId,
                'checked_by_name' => $checkerName !== '' ? $checkerName : '-',
                'mode' => in_array($row->mode ?? '', ['standard', 'grid'], true) ? $row->mode : 'standard',
                'follow_up_status' => $this->mapFollowUpStatus($row->follow_up_status ?? null),
                'follow_up_note' => $row->follow_up_note ?? null,
                'follow_up_date' => $this->toDate($row->follow_up_date ?? null),
                'created_at' => $this->toDateTime($row->created_at ?? null) ?? now(),
                'updated_at' => now(),
            ];
            $written++;

            if (count($rows) >= 1000) {
                $this->flushChecklistLogs($rows);
                $rows = [];
            }
        }

        $this->flushChecklistLogs($rows);
        $this->report['checklist_logs'] = ['read' => $read, 'written' => $written, 'errors' => $errors];
    }

    /**
     * Upsert one chunk without deleting target rows. Legacy-id is preferred;
     * business-key matching adopts rows imported by older importer versions.
     */
    protected function flushChecklistLogs(array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $legacyIds = array_values(array_unique(array_column($rows, 'legacy_id')));
        $inventoryIds = array_values(array_unique(array_column($rows, 'inventory_id')));
        $periodKeys = array_values(array_unique(array_column($rows, 'period_key')));

        $existing = DB::table('checklist_logs')
            ->select(['id', 'legacy_id', 'inventory_id', 'checklist_master_id', 'period_key', 'time_slot'])
            ->where(function ($query) use ($legacyIds, $inventoryIds, $periodKeys) {
                $query->whereIn('legacy_id', $legacyIds)
                    ->orWhere(function ($candidate) use ($inventoryIds, $periodKeys) {
                        $candidate->whereNull('legacy_id')
                            ->whereIn('inventory_id', $inventoryIds)
                            ->whereIn('period_key', $periodKeys);
                    });
            })
            ->orderBy('id')
            ->get();

        $byLegacyId = [];
        $byBusinessKey = [];
        foreach ($existing as $existingRow) {
            if ($existingRow->legacy_id !== null) {
                $byLegacyId[(int) $existingRow->legacy_id] = (int) $existingRow->id;
                continue;
            }

            $key = $this->checklistLogKey(
                $existingRow->inventory_id,
                $existingRow->checklist_master_id,
                $existingRow->period_key,
                $existingRow->time_slot
            );
            $byBusinessKey[$key][] = (int) $existingRow->id;
        }

        $updates = [];
        $inserts = [];
        foreach ($rows as $row) {
            $targetId = $byLegacyId[$row['legacy_id']] ?? null;
            if (! $targetId) {
                $key = $this->checklistLogKey(
                    $row['inventory_id'],
                    $row['checklist_master_id'],
                    $row['period_key'],
                    $row['time_slot']
                );
                if (! empty($byBusinessKey[$key])) {
                    $targetId = array_shift($byBusinessKey[$key]);
                }
            }

            if ($targetId) {
                $updates[] = ['id' => $targetId] + $row;
            } else {
                $inserts[] = $row;
            }
        }

        $updateColumns = [
            'legacy_id', 'inventory_id', 'asset_item_type_id', 'checklist_master_id',
            'check_date', 'period_key', 'time_slot', 'status', 'remark', 'photo',
            'checked_by_user_id', 'checked_by_name', 'mode', 'follow_up_status',
            'follow_up_note', 'follow_up_date', 'created_at', 'updated_at',
        ];

        if ($updates !== []) {
            DB::table('checklist_logs')->upsert($updates, ['id'], $updateColumns);
        }
        if ($inserts !== []) {
            DB::table('checklist_logs')->upsert($inserts, ['legacy_id'], $updateColumns);
        }
    }

    protected function checklistLogKey($inventoryId, $questionId, $periodKey, $timeSlot): string
    {
        return implode("\x1F", [
            (string) $inventoryId,
            (string) $questionId,
            (string) $periodKey,
            $timeSlot === null ? '<NULL>' : (string) $timeSlot,
        ]);
    }

    protected function mapRole(string $role): string
    {
        $role = strtolower(trim($role));

        return in_array($role, ['admin', 'compliance', 'security', 'staff', 'auditor', 'office'], true) ? $role : 'staff';
    }

    protected function mapStatus(string $status): string
    {
        return match (strtolower(trim($status))) {
            'need repair', 'need_repair' => 'need_repair',
            'not active', 'not_active', 'inactive' => 'not_active',
            default => 'good',
        };
    }

    protected function mapChecklistStatus($status): string
    {
        $status = strtolower(trim((string) ($status ?? '')));

        return match ($status) {
            'not_ok', 'not ok', 'not-ok', 'ng' => 'not_ok',
            'na', 'n/a' => 'na',
            default => 'ok',
        };
    }

    protected function mapFollowUpStatus($status): ?string
    {
        $status = strtolower(trim((string) ($status ?? '')));

        return in_array($status, ['open', 'monitoring', 'closed'], true) ? $status : null;
    }

    protected function toDate($value): ?string
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '' || str_starts_with($value, '0000')) {
            return null;
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $value, $matches)
            && checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1])) {
            return "{$matches[1]}-{$matches[2]}-{$matches[3]}";
        }

        return null;
    }

    protected function toDateTime($value): ?string
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '' || str_starts_with($value, '0000')) {
            return null;
        }
        $timestamp = strtotime($value);

        return $timestamp === false ? null : date('Y-m-d H:i:s', $timestamp);
    }

    protected function periodKeyToDate(string $periodKey): ?string
    {
        $periodKey = preg_replace('/-W[1-4]$/', '', trim($periodKey));
        if ($date = $this->toDate($periodKey)) {
            return $date;
        }
        if (preg_match('/^\d{4}-\d{2}$/', $periodKey)) {
            return $periodKey.'-01';
        }

        return null;
    }

    protected function toJsonOrNull($value): ?string
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $value : null;
    }

    protected function rows(string $reportKey, string $legacyTable): iterable
    {
        if (! Schema::connection('legacy')->hasTable($legacyTable)) {
            $this->report[$reportKey] = ['read' => 0, 'written' => 0, 'errors' => [], 'skipped' => true];

            return [];
        }

        return DB::connection('legacy')->table($legacyTable)->orderBy('id')->cursor();
    }

    protected function write(string $reportKey, callable $callback): void
    {
        $this->report[$reportKey] ??= ['read' => 0, 'written' => 0, 'errors' => []];
        $this->report[$reportKey]['read']++;

        try {
            // Dry-runs execute the exact write path inside a transaction which is
            // rolled back by run(); this also builds and validates every ID map.
            $callback();
            $this->report[$reportKey]['written']++;
        } catch (\Throwable $e) {
            $this->report[$reportKey]['errors'][] = $e->getMessage();
        }
    }

    protected function mapId(string $table, $legacyId, $newId): void
    {
        if ($legacyId !== null && $newId !== null) {
            $this->map[$table][$legacyId] = (int) $newId;
        }
    }

    protected function mapped(string $table, $legacyId): ?int
    {
        return $this->map[$table][$legacyId] ?? null;
    }
}
