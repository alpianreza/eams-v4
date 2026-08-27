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
 * Legacy CI4 → Laravel data import (2L). Reads the READ-ONLY `legacy` connection, writes
 * the clean Laravel DB. Repeatable + idempotent (upsert by business key), dry-run capable,
 * collects an error report. Missing legacy tables are SKIPPED (never fatal); FK links use a
 * legacy-id → new-id map; model observers suppressed (withoutEvents) so re-runs are clean.
 *
 * Column mapping verified against the REAL legacy dump (eams_database.sql):
 *  - checklist_logs.checklist_template_id → checklist_master.id (legacy live join).
 *  - asset_item_types.inventory_category_id → inventory_categories.id.
 *  - users.page_access (JSON text) / wa_number / photo carried.
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

    protected function importUsers(): void
    {
        foreach ($this->rows('users', 'users') as $row) {
            $this->write('users', function () use ($row) {
                $username = (string) $row->username;

                // Query-builder upsert (NOT the model) so the legacy bcrypt password hash is
                // carried EXACTLY (the model's 'hashed' cast would re-hash and break logins).
                DB::table('users')->upsert([
                    'username' => $username,
                    'name' => $row->name ?? $username,
                    'email' => $row->email ?? null,
                    'password' => (string) ($row->password ?? ''),
                    'photo' => $row->photo ?? null,
                    'role' => $this->mapRole((string) ($row->role ?? 'staff')),
                    'permission' => in_array($row->permission ?? 'read', ['read', 'write'], true) ? $row->permission : 'read',
                    'page_access' => $this->toJsonOrNull($row->page_access ?? null),
                    'status' => ($row->status ?? 'active') === 'active' ? 'active' : 'inactive',
                    'wa_number' => $row->wa_number ?? null,
                    'created_at' => now(),
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
                $area = Area::withoutEvents(fn () => Area::updateOrCreate(['name' => (string) $row->name], ['active' => (bool) ($row->active ?? true)]));
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
                    ['code' => $row->code ?? strtoupper(substr((string) $row->name, 0, 3)), 'active' => (bool) ($row->active ?? true)]
                ));
                $this->mapId('inventory_categories', $row->id ?? 0, $cat->id);
            });
        }
    }

    protected function importItemTypes(): void
    {
        foreach ($this->rows('asset_item_types', 'asset_item_types') as $row) {
            $this->write('asset_item_types', function () use ($row) {
                // FIX: legacy FK column is `inventory_category_id` (bukan category_id).
                $categoryId = $this->mapped('inventory_categories', $row->inventory_category_id ?? $row->category_id ?? 0) ?? InventoryCategory::value('id');
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
                $this->mapId('asset_item_types', $row->id ?? 0, $itemType->id);
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
                    ['asset_code' => (string) $row->asset_code],   // Q-020: preserved exactly
                    [
                        'inventory_category_id' => $categoryId,
                        'asset_item_type_id' => $itemTypeId,
                        'area_id' => $areaId,
                        'type_description' => $row->type_description ?? null,
                        'specific_area' => $row->specific_area ?? null,
                        'status' => $this->mapStatus((string) ($row->status ?? '')),
                        'qty' => (int) ($row->qty ?? 1),
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
                    throw new \RuntimeException('question: item_type tidak ter-resolve');
                }
                $q = ChecklistMaster::withoutEvents(fn () => ChecklistMaster::updateOrCreate(
                    ['asset_item_type_id' => $itemTypeId, 'question' => (string) $row->question],
                    [
                        'frequency' => in_array($row->frequency ?? '', ['daily', 'weekly', 'monthly'], true) ? $row->frequency : null,
                        'require_photo' => (bool) ($row->require_photo ?? false),
                        'active' => (bool) ($row->active ?? true),
                    ]
                ));
                $this->mapId('checklist_master', $row->id ?? 0, $q->id);
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

        // Preload lookup maps (1 query each) — jangan query per baris untuk ~100k log.
        $usersByName = User::pluck('id', 'name')->all();
        $inventoryTypeById = ComplianceInventory::pluck('asset_item_type_id', 'id')->all();

        $rows = [];
        $read = 0;
        $written = 0;
        $errors = [];
        $cleared = false;

        foreach ($legacy->table('checklist_logs')->orderBy('id')->cursor() as $row) {
            $read++;

            $invId = $this->mapped('compliance_inventories', $row->inventory_id ?? 0);
            // FIX: legacy FK column is `checklist_template_id` → references checklist_master.id.
            $questionId = $this->mapped('checklist_master', $row->checklist_template_id ?? 0);
            if (! $invId || ! $questionId) {
                $errors[] = "log#{$row->id}: inventory/question tidak ter-resolve (inv_id={$row->inventory_id}, template_id={$row->checklist_template_id})";
                continue;
            }

            // Q-006: checked_by (string name) → checked_by_user_id + checked_by_name snapshot.
            $checkerName = trim((string) ($row->checked_by ?? ''));
            $checkerId = $checkerName !== '' ? ($usersByName[$checkerName] ?? null) : null;

            // check_date: nilai legacy, kalau tidak valid → derive dari period_key.
            $checkDate = $this->toDate($row->check_date ?? null) ?? $this->periodKeyToDate((string) ($row->period_key ?? ''));
            if (! $checkDate) {
                $errors[] = "log#{$row->id}: check_date tidak valid (period_key={$row->period_key})";
                continue;
            }

            $rows[] = [
                'inventory_id' => $invId,
                'asset_item_type_id' => $inventoryTypeById[$invId] ?? null,
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
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $written++;

            if (count($rows) >= 1000) {
                $this->flushChecklistLogs($rows, $cleared);
                $rows = [];
            }
        }

        if ($rows !== []) {
            $this->flushChecklistLogs($rows, $cleared);
        }

        $this->report['checklist_logs'] = ['read' => $read, 'written' => $written, 'errors' => $errors];
    }

    protected function flushChecklistLogs(array &$rows, bool &$cleared): void
    {
        if ($this->dryRun || $rows === []) {
            return;
        }

        if (! $cleared) {
            // Full-replace untuk tabel turunan ini: hapus sekali, lalu bulk insert per chunk.
            // Idempoten secara efek (hasil akhir sama tiap run).
            DB::table('checklist_logs')->delete();
            $cleared = true;
        }

        DB::table('checklist_logs')->insert($rows);
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

    /** BR-11/Q-001: normalisasi status checklist legacy → ok|not_ok|na. 'ng' → not_ok; ''/tak dikenal → ok. */
    protected function mapChecklistStatus($status): string
    {
        $s = strtolower(trim((string) ($status ?? '')));
        return match ($s) {
            'not_ok', 'not ok', 'not-ok', 'ng' => 'not_ok',
            'na', 'n/a' => 'na',
            default => 'ok',
        };
    }

    protected function mapFollowUpStatus($status): ?string
    {
        $s = strtolower(trim((string) ($status ?? '')));
        return in_array($s, ['open', 'monitoring', 'closed'], true) ? $s : null;
    }

    /** Terima Y-m-d / Y-m-d H:i:s; tolak zero-date ('0000-00-00')/invalid → null. */
    protected function toDate($value): ?string
    {
        $v = trim((string) ($value ?? ''));
        if ($v === '' || str_starts_with($v, '0000')) {
            return null;
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $v, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return "{$m[1]}-{$m[2]}-{$m[3]}";
        }
        return null;
    }

    /** period_key → tanggal valid: daily Y-m-d apa adanya; monthly Y-m → -01; weekly Y-m-Wn → -01 (seperti legacy). */
    protected function periodKeyToDate(string $periodKey): ?string
    {
        $pk = preg_replace('/-W[1-4]$/', '', trim($periodKey)); // buang suffix -Wn
        if ($d = $this->toDate($pk)) {
            return $d;                          // daily Y-m-d
        }
        if (preg_match('/^\d{4}-\d{2}$/', $pk)) {
            return $pk.'-01';                   // monthly / weekly-stripped Y-m → -01
        }
        return null;
    }

    /** page_access legacy (text JSON) → string JSON valid untuk kolom json; invalid/kosong → null. */
    protected function toJsonOrNull($value): ?string
    {
        $v = trim((string) ($value ?? ''));
        if ($v === '') {
            return null;
        }
        json_decode($v);
        return json_last_error() === JSON_ERROR_NONE ? $v : null;
    }

    protected function rows(string $reportKey, string $legacyTable): iterable
    {
        if (! Schema::connection('legacy')->hasTable($legacyTable)) {
            $this->report[$reportKey] = ['read' => 0, 'written' => 0, 'errors' => [], 'skipped' => true];

            return [];
        }

        return DB::connection('legacy')->table($legacyTable)->orderBy('id')->cursor();
    }

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
