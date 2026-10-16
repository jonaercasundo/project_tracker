<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, true> */
    private array $tablesCreatedForPretend = [];

    /** @var array<string, string> */
    private array $pretendAlignedColumns = [];

    public function up(): void
    {
        $this->assertParentKey('companies', 'company_id');
        $this->assertParentKey('users', 'user_id');
        $this->assertParentKey('project_information', 'id');
        $this->assertParentKey('lots', 'id');
        $this->assertParentKey('ppl_forms', 'id');
        $this->assertParentKey('projects', 'project_id');

        $this->ensurePlansTable();
        $this->ensureVersionsTable();
        $this->ensureCostItemsTable();

        $this->ensureIndex('project_financial_plans', ['company_id', 'status', 'updated_at'], 'project_financial_plans_company_status_updated_index');
        $this->ensureIndex('project_financial_plans', ['company_id', 'project_id'], 'project_financial_plans_company_project_index');
        $this->ensureIndex('project_financial_plans', ['project_id'], 'project_financial_plans_project_index');
        $this->ensureIndex('project_financial_plan_versions', ['project_financial_plan_id', 'version_number'], 'project_financial_plan_versions_number_unique', true);
        $this->ensureIndex('project_financial_plan_versions', ['project_financial_plan_id', 'created_at'], 'project_financial_plan_versions_history_index');
        $this->ensureIndex('project_financial_plan_cost_items', ['project_financial_plan_version_id', 'category'], 'project_financial_plan_cost_category_index');

        $this->ensureForeignKey('project_financial_plans', 'company_id', 'companies', 'company_id', 'restrict');
        $this->ensureForeignKey('project_financial_plans', 'project_id', 'projects', 'project_id', 'set null');
        $this->ensureForeignKey('project_financial_plans', 'project_information_id', 'project_information', 'id', 'set null');
        $this->ensureForeignKey('project_financial_plans', 'lot_id', 'lots', 'id', 'set null');
        $this->ensureForeignKey('project_financial_plans', 'ppl_form_id', 'ppl_forms', 'id', 'set null');
        $this->ensureForeignKey('project_financial_plans', 'created_by', 'users', 'user_id', 'restrict');
        $this->ensureForeignKey('project_financial_plans', 'updated_by', 'users', 'user_id', 'restrict');
        $this->ensureForeignKey('project_financial_plan_versions', 'project_financial_plan_id', 'project_financial_plans', 'id', 'cascade');
        $this->ensureForeignKey('project_financial_plan_versions', 'created_by', 'users', 'user_id', 'restrict');
        $this->ensureForeignKey('project_financial_plan_cost_items', 'project_financial_plan_version_id', 'project_financial_plan_versions', 'id', 'cascade');
    }

    public function down(): void
    {
        foreach (['project_financial_plan_cost_items', 'project_financial_plan_versions', 'project_financial_plans'] as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new RuntimeException("Cannot roll back populated {$table}; preserve financial plan history first.");
            }
        }

        Schema::dropIfExists('project_financial_plan_cost_items');
        Schema::dropIfExists('project_financial_plan_versions');
        Schema::dropIfExists('project_financial_plans');
    }

    private function ensurePlansTable(): void
    {
        if (! $this->schemaRead(fn (): bool => Schema::hasTable('project_financial_plans'))) {
            Schema::create('project_financial_plans', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_id');
                $table->integer('project_id')->nullable();
                $table->foreignId('project_information_id')->nullable();
                $table->foreignId('lot_id')->nullable();
                $table->foreignId('ppl_form_id')->nullable();
                $table->string('name');
                $table->string('status', 24)->default('draft');
                $table->integer('created_by');
                $table->integer('updated_by');
                $table->timestamps();
                $table->index(['company_id', 'status', 'updated_at'], 'project_financial_plans_company_status_updated_index');
                $table->index(['company_id', 'project_id'], 'project_financial_plans_company_project_index');
                $table->index('project_id', 'project_financial_plans_project_index');
                $table->foreign('company_id', 'project_financial_plans_company_id_foreign')->references('company_id')->on('companies')->onDelete('restrict');
                $table->foreign('project_id', 'project_financial_plans_project_id_foreign')->references('project_id')->on('projects')->nullOnDelete();
                $table->foreign('project_information_id', 'project_financial_plans_project_information_id_foreign')->references('id')->on('project_information')->nullOnDelete();
                $table->foreign('lot_id', 'project_financial_plans_lot_id_foreign')->references('id')->on('lots')->nullOnDelete();
                $table->foreign('ppl_form_id', 'project_financial_plans_ppl_form_id_foreign')->references('id')->on('ppl_forms')->nullOnDelete();
                $table->foreign('created_by', 'project_financial_plans_created_by_foreign')->references('user_id')->on('users')->onDelete('restrict');
                $table->foreign('updated_by', 'project_financial_plans_updated_by_foreign')->references('user_id')->on('users')->onDelete('restrict');
            });

            if ($this->isPretending()) {
                $this->tablesCreatedForPretend['project_financial_plans'] = true;
            }
        }

        if (isset($this->tablesCreatedForPretend['project_financial_plans'])) {
            return;
        }

        $this->assertColumns('project_financial_plans', [
            'id' => 'bigint_unsigned',
            'company_id' => 'bigint_unsigned',
            'project_id' => 'integer',
            'project_information_id' => 'bigint_unsigned',
            'lot_id' => 'bigint_unsigned',
            'ppl_form_id' => 'bigint_unsigned',
            'name' => 'string',
            'status' => 'string',
            'created_by' => 'integer',
            'updated_by' => 'integer',
            'created_at' => 'timestamp',
            'updated_at' => 'timestamp',
        ]);
        $this->assertPrimaryKey('project_financial_plans', 'id');

        $this->alignIntegerColumnToParent('project_financial_plans', 'project_id', 'projects', 'project_id', true);
        $this->alignIntegerColumnToParent('project_financial_plans', 'created_by', 'users', 'user_id');
        $this->alignIntegerColumnToParent('project_financial_plans', 'updated_by', 'users', 'user_id');
    }

    private function ensureVersionsTable(): void
    {
        if (! $this->schemaRead(fn (): bool => Schema::hasTable('project_financial_plan_versions'))) {
            Schema::create('project_financial_plan_versions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('project_financial_plan_id');
                $table->unsignedInteger('version_number');
                $table->string('status', 24)->default('draft');
                $table->string('change_summary')->nullable();
                $table->json('input_data');
                $table->json('calculation_results')->nullable();
                $table->json('assumptions_snapshot')->nullable();
                $table->json('factory_cost_snapshot')->nullable();
                $table->integer('created_by');
                $table->timestamps();
                $table->unique(['project_financial_plan_id', 'version_number'], 'project_financial_plan_versions_number_unique');
                $table->index(['project_financial_plan_id', 'created_at'], 'project_financial_plan_versions_history_index');
                $table->foreign('project_financial_plan_id', 'project_financial_plan_versions_plan_fk')->references('id')->on('project_financial_plans')->cascadeOnDelete();
                $table->foreign('created_by', 'project_financial_plan_versions_created_by_foreign')->references('user_id')->on('users')->onDelete('restrict');
            });

            if ($this->isPretending()) {
                $this->tablesCreatedForPretend['project_financial_plan_versions'] = true;
            }
        }

        if (isset($this->tablesCreatedForPretend['project_financial_plan_versions'])) {
            return;
        }

        $this->assertColumns('project_financial_plan_versions', [
            'id' => 'bigint_unsigned',
            'project_financial_plan_id' => 'bigint_unsigned',
            'version_number' => 'integer_unsigned',
            'status' => 'string',
            'change_summary' => 'string',
            'input_data' => 'json',
            'calculation_results' => 'json',
            'assumptions_snapshot' => 'json',
            'factory_cost_snapshot' => 'json',
            'created_by' => 'integer',
            'created_at' => 'timestamp',
            'updated_at' => 'timestamp',
        ]);
        $this->assertPrimaryKey('project_financial_plan_versions', 'id');

        $this->alignIntegerColumnToParent('project_financial_plan_versions', 'created_by', 'users', 'user_id');
    }

    private function ensureCostItemsTable(): void
    {
        if (! $this->schemaRead(fn (): bool => Schema::hasTable('project_financial_plan_cost_items'))) {
            Schema::create('project_financial_plan_cost_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('project_financial_plan_version_id');
                $table->string('category', 80);
                $table->string('label');
                $table->decimal('amount', 15, 2)->default(0);
                $table->string('calculation_basis', 40)->nullable();
                $table->decimal('rate', 12, 6)->nullable();
                $table->string('source_type', 80)->nullable();
                $table->string('source_reference')->nullable();
                $table->json('source_snapshot')->nullable();
                $table->timestamps();
                $table->index(['project_financial_plan_version_id', 'category'], 'project_financial_plan_cost_category_index');
                $table->foreign('project_financial_plan_version_id', 'project_financial_plan_cost_items_version_fk')->references('id')->on('project_financial_plan_versions')->cascadeOnDelete();
            });

            if ($this->isPretending()) {
                $this->tablesCreatedForPretend['project_financial_plan_cost_items'] = true;
            }
        }

        if (isset($this->tablesCreatedForPretend['project_financial_plan_cost_items'])) {
            return;
        }

        $this->assertColumns('project_financial_plan_cost_items', [
            'id' => 'bigint_unsigned',
            'project_financial_plan_version_id' => 'bigint_unsigned',
            'category' => 'string',
            'label' => 'string',
            'amount' => 'decimal',
            'calculation_basis' => 'string',
            'rate' => 'decimal',
            'source_type' => 'string',
            'source_reference' => 'string',
            'source_snapshot' => 'json',
            'created_at' => 'timestamp',
            'updated_at' => 'timestamp',
        ]);
        $this->assertPrimaryKey('project_financial_plan_cost_items', 'id');
    }

    /** @param array<string, string> $columns */
    private function assertColumns(string $table, array $columns): void
    {
        $actualColumns = collect($this->schemaRead(fn (): array => Schema::getColumns($table)))->keyBy('name');
        $problems = [];

        foreach ($columns as $column => $expectedType) {
            if (! $actualColumns->has($column)) {
                $problems[] = "missing {$column}";

                continue;
            }

            if (! $this->matchesType($actualColumns->get($column)['type'], $expectedType)) {
                $problems[] = "{$column} has type {$actualColumns->get($column)['type']} (expected {$expectedType})";
            }
        }

        if ($problems !== []) {
            throw new RuntimeException("Unexpected {$table} schema: ".implode('; ', $problems).'. No existing table was dropped or overwritten.');
        }
    }

    private function matchesType(string $actual, string $expected): bool
    {
        $actual = strtolower($actual);
        $unsigned = str_contains($actual, 'unsigned');
        $base = preg_replace('/\(\d+(?:,\s*\d+)?\)/', '', $actual) ?? $actual;
        $base = trim(str_replace(['unsigned', 'zerofill'], '', $base));
        $base = $base === 'integer' ? 'int' : $base;

        return match ($expected) {
            'integer' => in_array($base, ['int', 'integer', 'bigint', 'smallint', 'mediumint'], true),
            'integer_signed' => in_array($base, ['int', 'integer'], true) && ! $unsigned,
            'integer_unsigned' => (in_array($base, ['int', 'integer'], true) && $unsigned) || (DB::connection()->getDriverName() === 'sqlite' && in_array($base, ['int', 'integer'], true)),
            'bigint_unsigned' => ($base === 'bigint' && $unsigned) || (DB::connection()->getDriverName() === 'sqlite' && in_array($base, ['int', 'integer'], true)),
            'string' => in_array($base, ['varchar', 'char', 'text', 'tinytext', 'mediumtext', 'longtext'], true),
            'json' => in_array($base, ['json', 'text', 'longtext'], true),
            'decimal' => in_array($base, ['decimal', 'numeric'], true),
            'timestamp' => in_array($base, ['timestamp', 'datetime'], true),
            default => false,
        };
    }

    private function assertParentKey(string $table, string $column): void
    {
        if (! $this->schemaRead(fn (): bool => Schema::hasTable($table) && Schema::hasColumn($table, $column))) {
            throw new RuntimeException("Required parent key {$table}.{$column} is missing.");
        }

        $isPrimary = collect($this->schemaRead(fn (): array => Schema::getIndexes($table)))->contains(
            fn (array $index): bool => ($index['primary'] ?? false) && ($index['columns'] ?? []) === [$column],
        );

        if (! $isPrimary) {
            throw new RuntimeException("Required parent key {$table}.{$column} is not the table's primary key.");
        }
    }

    private function assertPrimaryKey(string $table, string $column): void
    {
        $hasPrimaryKey = collect($this->schemaRead(fn (): array => Schema::getIndexes($table)))->contains(
            fn (array $index): bool => ($index['primary'] ?? false) && ($index['columns'] ?? []) === [$column],
        );

        if (! $hasPrimaryKey) {
            throw new RuntimeException("Expected {$table}.{$column} to be the primary key; no existing data was changed.");
        }
    }

    private function alignIntegerColumnToParent(string $table, string $column, string $parentTable, string $parentColumn, bool $nullable = false): void
    {
        $actual = $this->columnDefinition($table, $column);
        $parent = $this->columnDefinition($parentTable, $parentColumn);
        $actualSignature = $this->integerSignature($actual['type']);
        $parentSignature = $this->integerSignature($parent['type']);

        if ($actualSignature === null || $parentSignature === null) {
            throw new RuntimeException("{$table}.{$column} and {$parentTable}.{$parentColumn} must use integer key types.");
        }

        if ($actualSignature === $parentSignature) {
            return;
        }

        if ($this->schemaRead(fn (): bool => $this->hasValuesOutsideKeyRange($table, $column, $parentSignature))) {
            throw new RuntimeException("Cannot safely align {$table}.{$column} with {$parentTable}.{$parentColumn}; existing values exceed the parent key range.");
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $parentSignature, $nullable): void {
            $definition = match ($parentSignature) {
                'int' => $blueprint->integer($column),
                'int unsigned' => $blueprint->unsignedInteger($column),
                'bigint' => $blueprint->bigInteger($column),
                'bigint unsigned' => $blueprint->unsignedBigInteger($column),
                default => throw new RuntimeException("Unsupported integer key type {$parentSignature}."),
            };

            if ($nullable) {
                $definition->nullable();
            }

            $definition->change();
        });

        if ($this->isPretending()) {
            $this->pretendAlignedColumns[$table.'.'.$column] = $parentSignature;
        }
    }

    private function hasValuesOutsideKeyRange(string $table, string $column, string $targetSignature): bool
    {
        [$min, $max] = match ($targetSignature) {
            'int' => ['-2147483648', '2147483647'],
            'int unsigned' => ['0', '4294967295'],
            'bigint' => ['-9223372036854775808', '9223372036854775807'],
            'bigint unsigned' => ['0', '18446744073709551615'],
            default => throw new RuntimeException("Unsupported integer key type {$targetSignature}."),
        };

        return DB::table($table)
            ->whereNotNull($column)
            ->where(function ($query) use ($column, $min, $max): void {
                $query->where($column, '<', $min)->orWhere($column, '>', $max);
            })
            ->exists();
    }

    /** @return array{name: string, type: string, nullable: bool} */
    private function columnDefinition(string $table, string $column): array
    {
        $definition = collect($this->schemaRead(fn (): array => Schema::getColumns($table)))->firstWhere('name', $column);

        if (! $definition) {
            throw new RuntimeException("Required column {$table}.{$column} is missing.");
        }

        return $definition;
    }

    private function integerSignature(string $type): ?string
    {
        $normalized = strtolower($type);
        $unsigned = str_contains($normalized, 'unsigned');
        $base = preg_replace('/\(\d+\)/', '', $normalized) ?? $normalized;
        $base = trim(str_replace(['unsigned', 'zerofill'], '', $base));
        $base = $base === 'integer' ? 'int' : $base;

        return in_array($base, ['int', 'bigint'], true) ? $base.($unsigned ? ' unsigned' : '') : null;
    }

    /** @param list<string> $columns */
    private function ensureIndex(string $table, array $columns, string $name, bool $unique = false): void
    {
        if (isset($this->tablesCreatedForPretend[$table])) {
            return;
        }

        $indexes = $this->schemaRead(fn (): array => Schema::getIndexes($table));
        foreach ($indexes as $index) {
            if (($index['name'] ?? null) === $name) {
                if (($index['columns'] ?? []) === $columns && (bool) ($index['unique'] ?? false) === $unique) {
                    return;
                }

                throw new RuntimeException("Index {$name} already exists on {$table} with a different definition.");
            }

            if (($index['columns'] ?? []) === $columns && (! $unique || (bool) ($index['unique'] ?? false))) {
                return;
            }
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $name, $unique): void {
            if ($unique) {
                $blueprint->unique($columns, $name);
            } else {
                $blueprint->index($columns, $name);
            }
        });
    }

    private function ensureForeignKey(string $table, string $column, string $parentTable, string $parentColumn, string $onDelete): void
    {
        if (isset($this->tablesCreatedForPretend[$table])) {
            return;
        }

        $matchingColumn = null;
        foreach ($this->schemaRead(fn (): array => Schema::getForeignKeys($table)) as $foreignKey) {
            if (($foreignKey['columns'] ?? []) !== [$column]) {
                continue;
            }

            $matchingColumn = $foreignKey;
            break;
        }

        if ($matchingColumn !== null) {
            $matches = ($matchingColumn['foreign_table'] ?? null) === $parentTable
                && ($matchingColumn['foreign_columns'] ?? []) === [$parentColumn]
                && strtolower((string) ($matchingColumn['on_delete'] ?? 'restrict')) === $onDelete;

            if ($matches) {
                return;
            }

            throw new RuntimeException("Foreign key on {$table}.{$column} exists with a different parent or delete rule.");
        }

        $sourceSignature = $this->pretendAlignedColumns[$table.'.'.$column]
            ?? $this->integerSignature($this->columnDefinition($table, $column)['type']);
        $parentSignature = $this->integerSignature($this->columnDefinition($parentTable, $parentColumn)['type']);
        if ($sourceSignature === null || $sourceSignature !== $parentSignature) {
            throw new RuntimeException("Foreign key {$table}.{$column} cannot reference {$parentTable}.{$parentColumn}: integer types do not match.");
        }

        if ($this->schemaRead(fn (): bool => DB::table($table)->whereNotNull($column)->whereNotExists(function ($query) use ($parentTable, $parentColumn, $table, $column): void {
            $query->selectRaw('1')->from($parentTable)->whereColumn($parentTable.'.'.$parentColumn, $table.'.'.$column);
        })->exists())) {
            throw new RuntimeException("Foreign key {$table}.{$column} has values without matching {$parentTable}.{$parentColumn} records.");
        }

        $foreignKeyName = $this->foreignKeyName($table, $column);
        $existingNames = collect($this->schemaRead(fn (): array => Schema::getForeignKeys($table)))->pluck('name');
        if ($existingNames->contains($foreignKeyName)) {
            throw new RuntimeException("Foreign key name {$foreignKeyName} is already used with a different definition.");
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $parentTable, $parentColumn, $onDelete, $foreignKeyName): void {
            $blueprint->foreign($column, $foreignKeyName)
                ->references($parentColumn)
                ->on($parentTable)
                ->onDelete($onDelete);
        });
    }

    private function foreignKeyName(string $table, string $column): string
    {
        return match ($table.'.'.$column) {
            'project_financial_plan_versions.project_financial_plan_id' => 'project_financial_plan_versions_plan_fk',
            'project_financial_plan_cost_items.project_financial_plan_version_id' => 'project_financial_plan_cost_items_version_fk',
            default => $table.'_'.$column.'_foreign',
        };
    }

    private function isPretending(): bool
    {
        return DB::connection()->pretending();
    }

    private function schemaRead(Closure $callback): mixed
    {
        return DB::connection()->withoutPretending($callback);
    }
};
