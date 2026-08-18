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
 * (upsert by a stable business key), dry-run capable, and collects an error report.
 *
 * Model observers are suppressed during import (withoutEvents) so re-runs do not create
 * spurious checklist history rows.
 */
class LegacyImporter
{
    /** @var array<string, array<string, int|string[]>> */
    public array $report = [];

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
        $this->each('users', 'users', function ($row): void {
            User::withoutEvents(fn () => User::updateOrCreate(
                ['username' => (string) $row->username],
                [
                    'name' => $row->name ?? $row->username,
                    'email' => $row->email ?? null,
                    'password' => $row->password ?? '',           // CARRY bcrypt hash (compatible)
                    'role' => $this->mapRole($row->role ?? 'staff'),
                    'permission' => in_array($row->permission ?? 'read', ['read', 'write'], true) ? $row->permission : 'read',
                    'status' => ($row->status ?? 'active') === 'active' ? 'active' : 'inactive',
                ]
            ));
        });
    }

    protected function importAreas(): void
    {
        $this->each('areas', 'areas', function ($row): void {
            Area::withoutEvents(fn () => Area::updateOrCreate(['name' => (string) $row->name], ['active' => (bool) ($row->active ?? true)]));
        });
    }

    protected function importCategories(): void
    {
        $this->each('inventory_categories', 'inventory_categories', function ($row): void {
            InventoryCategory::withoutEvents(fn () => InventoryCategory::updateOrCreate(
                ['name' => (string) $row->name],
                ['code' => $row->code ?? strtoupper(substr((string) $row->name, 0, 3)), 'active' => (bool) ($row->active ?? true)]
            ));
        });
    }

    protected function importItemTypes(): void
    {
        $this->each('asset_item_types', 'asset_item_types', function ($row): void {
            $category = InventoryCategory::where('name', $row->category_name ?? '')->first()
                ?? InventoryCategory::find($row->category_id ?? 0);
            AssetItemType::withoutEvents(fn () => AssetItemType::updateOrCreate(
                ['code' => (string) $row->code],
                [
                    'inventory_category_id' => $category?->id ?? InventoryCategory::value('id'),
                    'name' => $row->name ?? $row->code,
                    'checklist_frequency' => in_array($row->checklist_frequency ?? '', ['daily', 'weekly', 'monthly'], true) ? $row->checklist_frequency : 'monthly',
                    'allow_na' => (bool) ($row->allow_na ?? false),
                    'active' => (bool) ($row->active ?? true),
                ]
            ));
        });
    }

    protected function importHolidays(): void
    {
        $this->each('holidays', 'holidays', function ($row): void {
            Holiday::withoutEvents(fn () => Holiday::updateOrCreate(
                ['holiday_date' => substr((string) $row->holiday_date, 0, 10)],
                ['description' => $row->description ?? null]
            ));
        });
    }

    protected function importEmployees(): void
    {
        $this->each('employees', 'employees', function ($row): void {
            Employee::withoutEvents(fn () => Employee::updateOrCreate(
                ['employee_id' => (string) $row->employee_id],
                ['name' => $row->name ?? $row->employee_id, 'division' => $row->division ?? null, 'position' => $row->position ?? null]
            ));
        });
    }

    /* ---------- compliance ---------- */

    protected function importInventories(): void
    {
        $this->each('compliance_inventories', 'compliance_inventory', function ($row): void {
            $category = InventoryCategory::find($row->category_id ?? 0) ?? InventoryCategory::where('name', $row->category_name ?? '')->first();
            $itemType = AssetItemType::findByCode((string) ($row->item_type_code ?? '')) ?? AssetItemType::find($row->item_type_id ?? 0);
            $area = Area::find($row->area_id ?? 0) ?? Area::where('name', $row->area_name ?? '')->first();

            ComplianceInventory::withoutEvents(fn () => ComplianceInventory::updateOrCreate(
                ['asset_code' => (string) $row->asset_code],   // Q-020: preserved exactly
                [
                    'inventory_category_id' => $category?->id,
                    'asset_item_type_id' => $itemType?->id,
                    'area_id' => $area?->id,
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
        });
    }

    protected function importInventoryPics(): void
    {
        // Legacy `pic` is a free-text name list → resolve to users, up to 2 equal PICs (Q-007).
        $this->each('compliance_inventory_pics', 'compliance_inventory', function ($row): void {
            $inventory = ComplianceInventory::where('asset_code', (string) $row->asset_code)->first();
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

    protected function importChecklistMaster(): void
    {
        $this->each('checklist_master', 'checklist_master', function ($row): void {
            $itemType = AssetItemType::findByCode((string) ($row->item_type_code ?? '')) ?? AssetItemType::find($row->item_type_id ?? 0);
            if (! $itemType) {
                return;
            }
            ChecklistMaster::withoutEvents(fn () => ChecklistMaster::updateOrCreate(
                ['asset_item_type_id' => $itemType->id, 'question' => (string) $row->question],
                [
                    'frequency' => in_array($row->frequency ?? '', ['daily', 'weekly', 'monthly'], true) ? $row->frequency : $itemType->checklist_frequency,
                    'require_photo' => (bool) ($row->require_photo ?? false),
                    'active' => (bool) ($row->active ?? true),
                ]
            ));
        });
    }

    protected function importChecklistLogs(): void
    {
        $this->each('checklist_logs', 'checklist_logs', function ($row): void {
            $inventory = ComplianceInventory::where('asset_code', (string) ($row->asset_code ?? ''))->first()
                ?? ComplianceInventory::find($row->inventory_id ?? 0);
            $question = isset($row->checklist_master_id) ? ChecklistMaster::find($row->checklist_master_id) : null;
            if (! $inventory || ! $question) {
                return;
            }

            // Q-006: checked_by (string name) → checked_by_user_id + checked_by_name snapshot.
            $checkerName = (string) ($row->checked_by ?? '');
            $checker = $checkerName !== '' ? User::where('name', $checkerName)->first() : null;

            ChecklistLog::withoutEvents(fn () => ChecklistLog::updateOrCreate(
                [
                    'inventory_id' => $inventory->id,
                    'checklist_master_id' => $question->id,
                    'period_key' => (string) $row->period_key,
                    'time_slot' => $row->time_slot ?? null,
                ],
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

    /** Read a legacy table (READ-ONLY) and apply $writer per row; track the report. */
    protected function each(string $reportKey, string $legacyTable, callable $writer): void
    {
        if (! Schema::connection('legacy')->hasTable($legacyTable)) {
            $this->report[$reportKey] = ['read' => 0, 'written' => 0, 'errors' => ['table missing'], 'skipped_table' => true];
            return;
        }

        $read = 0;
        $written = 0;
        $errors = [];

        foreach (DB::connection('legacy')->table($legacyTable)->orderBy('id')->cursor() as $row) {
            $read++;
            try {
                if (! $this->dryRun) {
                    $writer($row);
                }
                $written++;
            } catch (\Throwable $e) {
                $errors[] = ('id '.($row->id ?? '?').': '.$e->getMessage());
            }
        }

        $this->report[$reportKey] = ['read' => $read, 'written' => $written, 'errors' => $errors];
    }
}
