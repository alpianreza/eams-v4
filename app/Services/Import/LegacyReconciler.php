<?php

namespace App\Services\Import;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Read-only parity check between the legacy CI4 database and the Laravel
 * database (3L). Nothing is ever written: every finding points at concrete rows
 * so the legacy data can be corrected before or after `php artisan eams:import`.
 *
 * The vocabulary constants below describe the values LegacyImporter is able to
 * recognise. Anything outside them is silently coerced to a default, and that
 * silent coercion is exactly what this reconciler surfaces. Keep them in sync
 * with LegacyImporter::mapRole/mapStatus/mapChecklistStatus.
 */
class LegacyReconciler
{
    /**
     * Import pairs. `key` mirrors the business key LegacyImporter matches on, so
     * the number of distinct legacy keys is the number of target rows the import
     * is expected to produce.
     */
    public const PAIRS = [
        ['legacy' => 'users', 'target' => 'users', 'key' => ['username']],
        ['legacy' => 'areas', 'target' => 'areas', 'key' => ['name']],
        ['legacy' => 'inventory_categories', 'target' => 'inventory_categories', 'key' => ['name']],
        ['legacy' => 'asset_item_types', 'target' => 'asset_item_types', 'key' => ['code']],
        ['legacy' => 'holidays', 'target' => 'holidays', 'key' => ['holiday_date']],
        ['legacy' => 'employees', 'target' => 'employees', 'key' => ['employee_id']],
        ['legacy' => 'compliance_inventory', 'target' => 'compliance_inventories', 'key' => ['asset_code']],
        ['legacy' => 'checklist_master', 'target' => 'checklist_master', 'key' => ['item_type_id', 'question']],
        ['legacy' => 'checklist_logs', 'target' => 'checklist_logs', 'key' => ['id'], 'imported_only' => true],
    ];

    /** target table => column => [referenced table, referenced column] */
    public const TARGET_FOREIGN_KEYS = [
        'asset_item_types' => [
            'inventory_category_id' => ['inventory_categories', 'id'],
        ],
        'compliance_inventories' => [
            'inventory_category_id' => ['inventory_categories', 'id'],
            'asset_item_type_id' => ['asset_item_types', 'id'],
            'area_id' => ['areas', 'id'],
        ],
        'compliance_inventory_pics' => [
            'compliance_inventory_id' => ['compliance_inventories', 'id'],
            'user_id' => ['users', 'id'],
        ],
        'checklist_master' => [
            'asset_item_type_id' => ['asset_item_types', 'id'],
        ],
        'checklist_logs' => [
            'inventory_id' => ['compliance_inventories', 'id'],
            'asset_item_type_id' => ['asset_item_types', 'id'],
            'checklist_master_id' => ['checklist_master', 'id'],
            'checked_by_user_id' => ['users', 'id'],
        ],
        'checklist_log_histories' => [
            'checklist_log_id' => ['checklist_logs', 'id'],
        ],
    ];

    /**
     * Legacy foreign keys. `columns` lists candidate column names because the
     * legacy schema is not consistent (inventory_category_id vs category_id).
     */
    public const LEGACY_FOREIGN_KEYS = [
        'asset_item_types' => [
            ['columns' => ['inventory_category_id', 'category_id'], 'references' => ['inventory_categories', 'id']],
        ],
        'compliance_inventory' => [
            ['columns' => ['category_id'], 'references' => ['inventory_categories', 'id']],
            ['columns' => ['item_type_id'], 'references' => ['asset_item_types', 'id']],
            ['columns' => ['area_id'], 'references' => ['areas', 'id']],
        ],
        'checklist_master' => [
            ['columns' => ['item_type_id'], 'references' => ['asset_item_types', 'id']],
        ],
        'checklist_logs' => [
            ['columns' => ['inventory_id'], 'references' => ['compliance_inventory', 'id']],
            ['columns' => ['checklist_template_id'], 'references' => ['checklist_master', 'id']],
        ],
    ];

    public const TARGET_UNIQUE_KEYS = [
        'users' => ['username'],
        'asset_item_types' => ['code'],
        'compliance_inventories' => ['asset_code'],
        'checklist_logs' => ['legacy_id'],
    ];

    /** Duplicates here explain why the target has fewer rows than the legacy. */
    public const LEGACY_DUPLICATE_KEYS = [
        'users' => ['username'],
        'areas' => ['name'],
        'inventory_categories' => ['name'],
        'asset_item_types' => ['code'],
        'employees' => ['employee_id'],
        'holidays' => ['holiday_date'],
        'compliance_inventory' => ['asset_code'],
    ];

    public const TARGET_ENUMS = [
        'users' => [
            'permission' => ['read', 'write'],
            'status' => ['active', 'inactive'],
        ],
        'asset_item_types' => [
            'checklist_frequency' => ['daily', 'weekly', 'monthly'],
        ],
        'compliance_inventories' => [
            'status' => ['good', 'need_repair', 'not_active'],
        ],
        'checklist_master' => [
            'frequency' => ['daily', 'weekly', 'monthly'],
        ],
        'checklist_logs' => [
            'status' => ['ok', 'not_ok', 'na'],
            'mode' => ['standard', 'grid'],
            'follow_up_status' => ['open', 'monitoring', 'closed'],
        ],
    ];

    /** Legacy values LegacyImporter recognises, lowercased and trimmed. */
    public const LEGACY_VOCABULARY = [
        'users' => [
            'role' => [
                'default' => 'staff',
                'recognized' => ['admin', 'compliance', 'security', 'staff', 'auditor', 'office'],
            ],
        ],
        'compliance_inventory' => [
            'status' => [
                'default' => 'good',
                'recognized' => ['good', 'need repair', 'need_repair', 'not active', 'not_active', 'inactive'],
            ],
        ],
        'checklist_logs' => [
            'status' => [
                'default' => 'ok',
                'recognized' => ['ok', 'not_ok', 'not ok', 'not-ok', 'ng', 'na', 'n/a'],
            ],
        ],
    ];

    protected ?string $legacyError = null;

    public function reconcile(array $options = []): array
    {
        $sampleLimit = max(1, (int) ($options['sampleLimit'] ?? 5));

        $report = [
            'generated_at' => now()->toIso8601String(),
            'legacy_database' => (string) config('database.connections.legacy.database'),
            'legacy_available' => $this->legacyAvailable(),
            'legacy_error' => $this->legacyError,
            'row_counts' => [],
            'legacy_duplicates' => [],
            'legacy_orphans' => [],
            'legacy_normalizations' => [],
            'target_orphans' => [],
            'target_duplicates' => [],
            'target_invalid_enums' => [],
            'checklist_log_parity' => null,
            'issues' => [],
        ];

        $this->collectRowCounts($report);
        $this->collectTargetIntegrity($report, $sampleLimit);

        if ($report['legacy_available']) {
            $this->collectLegacyDuplicates($report, $sampleLimit);
            $this->collectLegacyOrphans($report, $sampleLimit);
            $this->collectLegacyNormalizations($report, $sampleLimit);
            $this->collectChecklistLogParity($report, $sampleLimit);
        } else {
            $report['issues'][] = 'Koneksi legacy tidak tersedia: '.($this->legacyError ?? 'unknown error');
        }

        $report['ok'] = $report['issues'] === [];

        return $report;
    }

    protected function legacyAvailable(): bool
    {
        try {
            DB::connection('legacy')->getPdo();

            return true;
        } catch (\Throwable $e) {
            $this->legacyError = $e->getMessage();

            return false;
        }
    }

    protected function collectRowCounts(array &$report): void
    {
        foreach (self::PAIRS as $pair) {
            if (! $this->schema()->hasTable($pair['target'])) {
                continue;
            }

            $importedOnly = ($pair['imported_only'] ?? false)
                && $this->schema()->hasColumn($pair['target'], 'legacy_id');

            $targetQuery = DB::table($pair['target']);
            if ($importedOnly) {
                $targetQuery->whereNotNull('legacy_id');
            }
            $targetRows = $targetQuery->count();

            $legacyRows = null;
            $legacyKeys = null;

            if ($report['legacy_available'] && $this->schema(true)->hasTable($pair['legacy'])) {
                $legacyRows = DB::connection('legacy')->table($pair['legacy'])->count();
                $legacyKeys = $this->distinctLegacyKeys($pair['legacy'], $pair['key'], $legacyRows);
            }

            $report['row_counts'][] = [
                'legacy_table' => $pair['legacy'],
                'target_table' => $pair['target'].($importedOnly ? ' (imported)' : ''),
                'legacy_rows' => $legacyRows,
                'legacy_unique_keys' => $legacyKeys,
                'target_rows' => $targetRows,
                'delta' => $legacyKeys === null ? null : $targetRows - $legacyKeys,
            ];

            // Extra target rows are legitimate (data created inside the app), so
            // only a shortfall counts as a finding.
            $missing = $legacyKeys === null ? 0 : max(0, $legacyKeys - $targetRows);
            if ($missing > 0) {
                $report['issues'][] = sprintf(
                    '%s -> %s: %d baris legacy belum ada di target (kunci unik legacy %d, baris target %d)',
                    $pair['legacy'],
                    $pair['target'],
                    $missing,
                    $legacyKeys,
                    $targetRows
                );
            }
        }
    }

    protected function distinctLegacyKeys(string $table, array $key, int $totalRows): int
    {
        $columns = array_values(array_filter(
            $key,
            fn (string $column): bool => $this->schema(true)->hasColumn($table, $column)
        ));

        if ($columns === [] || $columns === ['id']) {
            return $totalRows;
        }

        $connection = DB::connection('legacy');
        $keys = $connection->table($table)->select($columns)->distinct();

        return $connection->query()->fromSub($keys, 'legacy_keys')->count();
    }

    protected function collectTargetIntegrity(array &$report, int $sampleLimit): void
    {
        foreach (self::TARGET_FOREIGN_KEYS as $table => $columns) {
            if (! $this->schema()->hasTable($table)) {
                continue;
            }

            foreach ($columns as $column => [$refTable, $refColumn]) {
                if (! $this->schema()->hasColumn($table, $column) || ! $this->schema()->hasTable($refTable)) {
                    continue;
                }

                $query = DB::table($table)
                    ->whereNotNull($table.'.'.$column)
                    ->whereNotExists(fn ($sub) => $sub->select(DB::raw('1'))
                        ->from($refTable)
                        ->whereColumn($refTable.'.'.$refColumn, $table.'.'.$column));

                $count = (clone $query)->count();
                if ($count === 0) {
                    continue;
                }

                $report['target_orphans'][] = [
                    'table' => $table,
                    'column' => $column,
                    'references' => $refTable.'.'.$refColumn,
                    'count' => $count,
                    'sample_ids' => (clone $query)->orderBy($table.'.id')->limit($sampleLimit)->pluck($table.'.id')->all(),
                ];
                $report['issues'][] = sprintf(
                    '%s.%s menunjuk %s yang tidak ada (%d baris)',
                    $table,
                    $column,
                    $refTable,
                    $count
                );
            }
        }

        foreach (self::TARGET_UNIQUE_KEYS as $table => $columns) {
            if (! $this->schema()->hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! $this->schema()->hasColumn($table, $column)) {
                    continue;
                }

                $duplicates = $this->duplicateGroups(DB::connection(), $table, $column, $sampleLimit);
                if ($duplicates['groups'] === 0) {
                    continue;
                }

                $report['target_duplicates'][] = ['table' => $table, 'column' => $column] + $duplicates;
                $report['issues'][] = sprintf(
                    '%s.%s duplikat pada %d nilai',
                    $table,
                    $column,
                    $duplicates['groups']
                );
            }
        }

        foreach (self::TARGET_ENUMS as $table => $columns) {
            if (! $this->schema()->hasTable($table)) {
                continue;
            }

            foreach ($columns as $column => $allowed) {
                if (! $this->schema()->hasColumn($table, $column)) {
                    continue;
                }

                $query = DB::table($table)->whereNotNull($column)->whereNotIn($column, $allowed);
                $count = (clone $query)->count();
                if ($count === 0) {
                    continue;
                }

                $report['target_invalid_enums'][] = [
                    'table' => $table,
                    'column' => $column,
                    'allowed' => $allowed,
                    'count' => $count,
                    'samples' => $this->samples($query, $table, $column, $sampleLimit, $this->schema()),
                ];
                $report['issues'][] = sprintf(
                    '%s.%s berisi %d nilai di luar enum [%s]',
                    $table,
                    $column,
                    $count,
                    implode(', ', $allowed)
                );
            }
        }
    }

    protected function collectLegacyDuplicates(array &$report, int $sampleLimit): void
    {
        $connection = DB::connection('legacy');

        foreach (self::LEGACY_DUPLICATE_KEYS as $table => $columns) {
            if (! $this->schema(true)->hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! $this->schema(true)->hasColumn($table, $column)) {
                    continue;
                }

                $duplicates = $this->duplicateGroups($connection, $table, $column, $sampleLimit);
                if ($duplicates['groups'] === 0) {
                    continue;
                }

                $report['legacy_duplicates'][] = ['table' => $table, 'column' => $column] + $duplicates;
                $report['issues'][] = sprintf(
                    'legacy %s.%s duplikat pada %d nilai; importer menggabungkannya jadi satu baris target',
                    $table,
                    $column,
                    $duplicates['groups']
                );
            }
        }
    }

    protected function collectLegacyOrphans(array &$report, int $sampleLimit): void
    {
        $connection = DB::connection('legacy');

        foreach (self::LEGACY_FOREIGN_KEYS as $table => $definitions) {
            if (! $this->schema(true)->hasTable($table)) {
                continue;
            }

            foreach ($definitions as $definition) {
                [$refTable, $refColumn] = $definition['references'];
                if (! $this->schema(true)->hasTable($refTable)) {
                    continue;
                }

                $column = null;
                foreach ($definition['columns'] as $candidate) {
                    if ($this->schema(true)->hasColumn($table, $candidate)) {
                        $column = $candidate;
                        break;
                    }
                }
                if ($column === null) {
                    continue;
                }

                // Legacy stores "no relation" as 0 as often as NULL.
                $query = $connection->table($table)
                    ->whereNotNull($table.'.'.$column)
                    ->where($table.'.'.$column, '>', 0)
                    ->whereNotExists(fn ($sub) => $sub->select(DB::raw('1'))
                        ->from($refTable)
                        ->whereColumn($refTable.'.'.$refColumn, $table.'.'.$column));

                $count = (clone $query)->count();
                if ($count === 0) {
                    continue;
                }

                $report['legacy_orphans'][] = [
                    'table' => $table,
                    'column' => $column,
                    'references' => $refTable.'.'.$refColumn,
                    'count' => $count,
                    'sample_ids' => (clone $query)->orderBy($table.'.id')->limit($sampleLimit)->pluck($table.'.id')->all(),
                ];
                $report['issues'][] = sprintf(
                    'legacy %s.%s menunjuk %s yang tidak ada (%d baris); baris ini gagal saat import',
                    $table,
                    $column,
                    $refTable,
                    $count
                );
            }
        }
    }

    protected function collectLegacyNormalizations(array &$report, int $sampleLimit): void
    {
        $connection = DB::connection('legacy');
        $grammar = $connection->getQueryGrammar();

        foreach (self::LEGACY_VOCABULARY as $table => $columns) {
            if (! $this->schema(true)->hasTable($table)) {
                continue;
            }

            foreach ($columns as $column => $definition) {
                if (! $this->schema(true)->hasColumn($table, $column)) {
                    continue;
                }

                $recognized = $definition['recognized'];
                $placeholders = implode(', ', array_fill(0, count($recognized), '?'));
                $expression = 'lower(trim(coalesce('.$grammar->wrap($table.'.'.$column).", '')))";

                $query = $connection->table($table)
                    ->whereRaw($expression.' not in ('.$placeholders.')', $recognized);

                $count = (clone $query)->count();
                if ($count === 0) {
                    continue;
                }

                $report['legacy_normalizations'][] = [
                    'table' => $table,
                    'column' => $column,
                    'normalized_to' => $definition['default'],
                    'recognized' => $recognized,
                    'count' => $count,
                    'samples' => $this->samples($query, $table, $column, $sampleLimit, $this->schema(true)),
                ];
                $report['issues'][] = sprintf(
                    'legacy %s.%s berisi %d nilai tak dikenali; importer memaksanya menjadi "%s"',
                    $table,
                    $column,
                    $count,
                    $definition['default']
                );
            }
        }
    }

    protected function collectChecklistLogParity(array &$report, int $sampleLimit): void
    {
        if (! $this->schema(true)->hasTable('checklist_logs') || ! $this->schema()->hasTable('checklist_logs')) {
            return;
        }

        if (! $this->schema()->hasColumn('checklist_logs', 'legacy_id')) {
            $report['issues'][] = 'Kolom checklist_logs.legacy_id belum tersedia. Jalankan php artisan migrate.';

            return;
        }

        $legacyRows = 0;
        $missingCount = 0;
        $missingSamples = [];
        $unresolvableDates = 0;
        $unresolvableSamples = [];
        $batch = [];

        $flush = function () use (&$batch, &$missingCount, &$missingSamples, $sampleLimit): void {
            if ($batch === []) {
                return;
            }

            $found = array_map(
                'intval',
                DB::table('checklist_logs')->whereIn('legacy_id', $batch)->pluck('legacy_id')->all()
            );

            foreach (array_diff($batch, $found) as $legacyId) {
                $missingCount++;
                if (count($missingSamples) < $sampleLimit) {
                    $missingSamples[] = $legacyId;
                }
            }

            $batch = [];
        };

        foreach (DB::connection('legacy')->table('checklist_logs')->orderBy('id')->cursor() as $row) {
            $legacyRows++;
            $batch[] = (int) ($row->id ?? 0);

            if (count($batch) >= 1000) {
                $flush();
            }

            if ($this->resolvableDate($row)) {
                continue;
            }

            $unresolvableDates++;
            if (count($unresolvableSamples) < $sampleLimit) {
                $unresolvableSamples[] = [
                    'id' => (int) ($row->id ?? 0),
                    'check_date' => $row->check_date ?? null,
                    'period_key' => $row->period_key ?? null,
                ];
            }
        }

        $flush();

        $extraCount = 0;
        $extraSamples = [];
        DB::table('checklist_logs')
            ->whereNotNull('legacy_id')
            ->orderBy('legacy_id')
            ->select('legacy_id')
            ->chunk(1000, function ($rows) use (&$extraCount, &$extraSamples, $sampleLimit): void {
                $ids = $rows->pluck('legacy_id')->map(fn ($id): int => (int) $id)->all();
                $found = DB::connection('legacy')->table('checklist_logs')
                    ->whereIn('id', $ids)->pluck('id')->map(fn ($id): int => (int) $id)->all();

                foreach (array_diff($ids, $found) as $legacyId) {
                    $extraCount++;
                    if (count($extraSamples) < $sampleLimit) {
                        $extraSamples[] = $legacyId;
                    }
                }
            });

        $report['checklist_log_parity'] = [
            'legacy_rows' => $legacyRows,
            'imported_rows' => DB::table('checklist_logs')->whereNotNull('legacy_id')->count(),
            'app_created_rows' => DB::table('checklist_logs')->whereNull('legacy_id')->count(),
            'missing_in_target' => $missingCount,
            'missing_sample_legacy_ids' => $missingSamples,
            'extra_in_target' => $extraCount,
            'extra_sample_legacy_ids' => $extraSamples,
            'unresolvable_dates' => $unresolvableDates,
            'unresolvable_date_samples' => $unresolvableSamples,
        ];

        if ($missingCount > 0) {
            $report['issues'][] = sprintf('%d baris legacy checklist_logs belum ada di target', $missingCount);
        }
        if ($extraCount > 0) {
            $report['issues'][] = sprintf(
                '%d baris target checklist_logs punya legacy_id yang sudah tidak ada di legacy',
                $extraCount
            );
        }
        if ($unresolvableDates > 0) {
            $report['issues'][] = sprintf(
                '%d baris legacy checklist_logs tanpa tanggal yang bisa diturunkan; importer melewatinya',
                $unresolvableDates
            );
        }
    }

    /** Mirrors LegacyImporter: check_date, falling back to period_key. */
    protected function resolvableDate(object $row): bool
    {
        return $this->toDate($row->check_date ?? null) !== null
            || $this->periodKeyToDate((string) ($row->period_key ?? '')) !== null;
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

    protected function duplicateGroups(Connection $connection, string $table, string $column, int $sampleLimit): array
    {
        $groupedQuery = fn () => $connection->table($table)
            ->select($column)
            ->whereNotNull($column)
            ->groupBy($column)
            ->havingRaw('count(*) > 1');

        return [
            'groups' => $connection->query()->fromSub($groupedQuery(), 'duplicate_keys')->count(),
            'sample_values' => $groupedQuery()->orderBy($column)->limit($sampleLimit)->pluck($column)->all(),
        ];
    }

    protected function samples($query, string $table, string $column, int $limit, $schema): array
    {
        if ($schema->hasColumn($table, 'id')) {
            return (clone $query)->orderBy('id')->limit($limit)->get(['id', $column])
                ->map(fn ($row): array => ['id' => $row->id, 'value' => $row->{$column}])
                ->all();
        }

        return (clone $query)->limit($limit)->get([$column])
            ->map(fn ($row): array => ['id' => null, 'value' => $row->{$column}])
            ->all();
    }

    protected function schema(bool $legacy = false)
    {
        return Schema::connection($legacy ? 'legacy' : DB::getDefaultConnection());
    }
}
