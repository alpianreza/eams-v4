<?php

namespace App\Services\Import;

use App\Models\Area;
use App\Models\AssetItemType;
use App\Models\ChecklistLog;
use App\Models\ChecklistMaster;
use App\Models\ComplianceInventory;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\InventoryCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Legacy CI4 → Laravel data import (2L). Reads from the READ-ONLY `legacy` connection
 * (never the runtime DB), writes to the clean Laravel DB. Repeatable + idempotent
 * (upsert by a stable business key), dry-run capable, collects an error report.
 *
 * - Missing legacy tables are SKIPPED (reported), never fatal.
 * - FK links use a legacy-id → new-id map built as master data imports.
 * - Model observers are suppressed (withoutEvents) so re-runs create no spurious history.
 */
class LegacyImporter
{
    public array $report = [];

    /** @var array<string, array<int|string, int>> legacy-id → new-id per table */
    protected array $map = [];

    public function __construct(protected bool $dryRun = false) {}

    public function run(): array
    {
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

        return $this->report;
    }

    /* ---------- master data ---------- */

    protected function importUsers(): void
    {
        foreach ($this->rows('users', 'users') as $row) {
            $this->write('users', function () use ($row) {
                $user = User::withoutEvents(fn () => User::updateOrCreate(
                    ['username' => (string) $row->username],
                    [
                        'name' => $row->name ?? $row->username,
                        'email' => $row->email ?? null,
                        'password' => $row->password ?? '',   // CARRY bcrypt hash (compatible)
                        'role' => $this->mapRole($row->role ?? 'staff'),
                        'permission' => in_array($row->permission ?? 'read', ['read', 'write'], true) ? $row->permission : 'read',
                        'status' => ($row->status ?? 'active') === 'active' ? 'active' : 'inactive',
                    ]
                ));
                $this->mapId('users', $row->id, $user->id);
            });
        }
    }

    protected function importAreas(): void
    {
        foreach ($this->rows('areas', 'areas') as $row) {
            $this->write('areas', function () use ($row) {
                $area = Area::withoutEvents(fn () => Area::updateOrCreate(['name' => (string) $row->name], ['active' => (bool) ($row->active ?? true)]));
                $this->mapId('areas', $row->id, $area->id);
            });
        }
    }

    protected function importCategories(): void
    {
        foreach ($this->rows('inventory_categories', 'inventory_categories') as $row) {
            $this->write('inventory_categories', function () use ($row) {
                $cat = InventoryCategory::withoutEvents(fn () => InventoryCategory::updateOrCreate(
                    ['name' => (string) $row->name],
                    ['code' => $row->code ?? strtoupper(substr((string) $row->name, 0, 3)), 'active' => (bool) ($row->active ?? true)]
                ));
                $this->mapId('inventory_categories', $row->id, $cat->id);
            });
        }
    }

    protected function importItemTypes(): void
    {
        foreach ($this->rows('asset_item_types', 'asset_item_types') as $row) {
            $this->write('asset_item_types', function () use ($row) {
                $categoryId = $this->mapped('inventory_categories', $row->category_id ?? 0) ?? InventoryCategory::value('id');
                $itemType = AssetItemType::withoutEvents(fn () => AssetItemType::updateOrCreate(
                    ['code' => (string) $row->code],
                    [
                        'inventory_category_id' => $categoryId,
                        'name' => $row->name ?? $row->code,
                        'checklist_frequency' => in_array($row->checklist_frequency ?? '', ['daily', 'weekly', 'monthly'], true) ? $row->checklist_frequency : 'monthly',
                        'allow_na' => (bool) ($row->allow_na ?? false),
                        'active' => (bool) ($row->active ?? true),
                    ]
                ));
                $this->mapId('asset_item_types', $row->id, $itemType->id);
            });
        }
    }

    protected function importHolidays(): void
    {
        foreach ($this->rows('holidays', 'holidays') as $row) {
            $this->write('holidays', function () use ($row) {
                Holiday::withoutEvents(fn () => Holiday::updateOrCreate(
                    ['holiday_date' => substr((string) $row->holiday_date, 0, 10)],
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
                    ['name' => $row->name ?? $row->employee_id, 'division' => $row->division ?? null, 'position' => $row->position ?? null]
                ));
            });
        }
    }

    /* ---------- compliance ---------- */

    protected function importInventories(): void
    {
        foreach ($this->rows('compliance_inventories', 'compliance_inventory') as $row) {
            $this->write('compliance_inventories', function () use ($row) {
                $categoryId = $this->mapped('inventory_categories', $row->category_id ?? 0);
                $itemTypeId = $this->mapped('asset_item_types', $row->item_type_id ?? 0);
                $areaId = $this->mapped('areas', $row->area_id ?? 0);

                // category/item_type are required — skip + REVIEW if unresolvable (no master imported).
                if (! $categoryId || ! $itemTypeId) {
                    throw new \RuntimeException("asset_code {$row->asset_code}: kategori/item-type tidak ter-resolve");
                }

                $inv = ComplianceInventory::withoutEvents(fn () => ComplianceInventory::updateOrCreate(
                    ['asset_code' => (string) $row->asset_code],   // Q-020: preserved exactly
                    [
                        'inventory_category_id' => $categoryId,
                        'asset_item_type_id' => $itemTypeId,
                        'area_id' => $areaId,
                        'type_description' => $row->type_description ?? null,
                        'specific_area' => $row->specific_area ?? null,
                        'status' => $this->mapStatus($row->status ?? ''),
                        'qty' => (int) ($row->qty ?? 1),
                        'remark' => $row->remark ?? null,
                        'expired_date' => $row->expired_date ? substr((string) $row->expired_date, 0, 10) : null,
                        'photo' => $row->photo ?? null,
                        'qr_image' => $row->qr_image ?? null,
                        'active' => (bool) ($row->active ?? true),
                    ]
                ));
                $this->mapId('compliance_inventories', $row->id, $inv->id);
            });
        }
    }

    protected function importInventoryPics(): void
    {
        // Legacy `pic` is a free-text name list → resolve to users, up to 2 equal PICs (Q-007).
        foreach ($this->rows('compliance_inventory_pics', 'compliance_inventory') as $row) {
            $this->write('compliance_inventory_pics', function () use ($row) {
                $invId = $this->mapped('compliance_inventories', $row->id ?? 0);
                $inventory = $invId ? ComplianceInventory::find($invId) : ComplianceInventory::where('asset_code', (string) ($row->asset_code ?? ''))->first();
                if (! $inventory) {
                    return;
                }
                $names = array_filter(array_map('trim', preg_split('/[-,\n]/', (string) ($row->pic ?? ''))));
                $userIds = [];
                foreach (array_slice($names, 0, 2) as $name) {  // max 2, equal (Q-007)
                    $user = User::where('name', $name)->first();
                    if ($user) {
                        $userIds[] = $user->id;
                    }
                }
                if ($userIds !== []) {
                    $inventory->pics()->syncWithoutDetaching($userIds);
                }
            });
        }
    }

    protected function importChecklistMaster(): void
    {
        foreach ($this->rows('checklist_master', 'checklist_master') as $row) {
            $this->write('checklist_master', function () use ($row) {
                $itemTypeId = $this->mapped('asset_item_types', $row->item_type_id ?? 0);
                if (! $itemTypeId) {
                    throw new \RuntimeException("question '{$row->question}': item_type tidak ter-resolve");
                }
                $q = ChecklistMaster::withoutEvents(fn () => ChecklistMaster::updateOrCreate(
                    ['asset_item_type_id' => $itemTypeId, 'question' => (string) $row->question],
                    [
                        'frequency' => in_array($row->frequency ?? '', ['daily', 'weekly', 'monthly'], true) ? $row->frequency : null,
                        'require_photo' => (bool) ($row->require_photo ?? false),
                        'active' => (bool) ($row->active ?? true),
                    ]
                ));
                $this->mapId('checklist_master', $row->id, $q->id);
            });
        }
    }

    protected function importChecklistLogs(): void
    {
        foreach ($this->rows('checklist_logs', 'checklist_logs') as $row) {
            $this->write('checklist_logs', function () use ($row) {
                $invId = $this->mapped('compliance_inventories', $row->inventory_id ?? 0);
                $questionId = $this->mapped('checklist_master', $row->checklist_master_id ?? 0);
                if (! $invId || ! $questionId) {
                    throw new \RuntimeException('checklist_log: inventory/question tidak ter-resolve');
                }
                $inventory = ComplianceInventory::find($invId);

                // Q-006: checked_by (string name) → checked_by_user_id + checked_by_name snapshot.
                $checkerName = trim((string) ($row->checked_by ?? ''));
                $checker = $checkerName !== '' ? User::where('name', $checkerName)->first() : null;

                ChecklistLog::withoutEvents(fn () => ChecklistLog::updateOrCreate(
                    ['inventory_id' => $invId, 'checklist_master_id' => $questionId, 'period_key' => (string) $row->period_key, 'time_slot' => $row->time_slot ?? null],
                    [
                        'asset_item_type_id' => $inventory->asset_item_type_id,
                        'check_date' => $row->check_date ? substr((string) $row->check_date, 0, 10) : substr((string) $row->period_key, 0, 10),
                        'status' => in_array($row->status ?? '', ['ok', 'not_ok', 'na'], true) ? $row->status : 'ok',
                        'remark' => $row->remark ?? null,
                        'photo' => $row->photo ?? null,
                        'checked_by_user_id' => $checker?->id,
                        'checked_by_name' => $checkerName !== '' ? $checkerName : ($checker?->name ?? '—'),
                        'mode' => in_array($row->mode ?? '', ['standard', 'grid'], true) ? $row->mode : 'standard',
                    ]
                ));
            });
        }
    }

    /* ---------- mapping helpers ---------- */

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

    /* ---------- infra ---------- */

    /** Legacy rows of a table (READ-ONLY). Missing table → empty + SKIPPED report (never fatal). */
    protected function rows(string $reportKey, string $legacyTable): iterable
    {
        if (! Schema::connection('legacy')->hasTable($legacyTable)) {
            $this->report[$reportKey] = ['read' => 0, 'written' => 0, 'errors' => [], 'skipped' => true];

            return [];
        }

        return DB::connection('legacy')->table($legacyTable)->orderBy('id')->cursor();
    }

    /** Apply $fn for one row; track read/written/errors in the report. */
    protected function write(string $reportKey, callable $fn): void
    {
        $this->report[$reportKey] ??= ['read' => 0, 'written' => 0, 'errors' => []];
        $this->report[$reportKey]['read']++;

        try {
            if (! $this->dryRun) {
                $fn();
            }
            $this->report[$reportKey]['written']++;
        } catch (\Throwable $e) {
            $this->report[$reportKey]['errors'][] = $e->getMessage();
        }
    }

    protected function mapId(string $table, $legacyId, $newId): void
    {
        $this->map[$table][$legacyId] = $newId;
    }

    protected function mapped(string $table, $legacyId): ?int
    {
        return $this->map[$table][$legacyId] ?? null;
    }
}
