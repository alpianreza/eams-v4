<?php

namespace Tests\Feature\Import;

use App\Services\Import\LegacyImporter;
use App\Services\Import\LegacyReconciler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegacyReconcileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUpLegacy(array $tables): void
    {
        config()->set('database.connections.legacy', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false,
        ]);
        DB::purge('legacy');
        foreach ($tables as $ddl) {
            DB::connection('legacy')->statement($ddl);
        }
    }

    protected function legacy(): \Illuminate\Database\Connection
    {
        return DB::connection('legacy');
    }

    protected function seedLegacy(): void
    {
        $this->setUpLegacy([
            'CREATE TABLE users (id INTEGER PRIMARY KEY, username TEXT, name TEXT, role TEXT, permission TEXT, status TEXT)',
            'CREATE TABLE areas (id INTEGER PRIMARY KEY, name TEXT, active INTEGER)',
            'CREATE TABLE inventory_categories (id INTEGER PRIMARY KEY, name TEXT, code TEXT, active INTEGER)',
            'CREATE TABLE asset_item_types (id INTEGER PRIMARY