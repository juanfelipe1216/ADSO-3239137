<?php

namespace Tests\Integration;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class SchemaConstraintsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function test_schema_has_only_six_business_tables_and_no_defaults(): void
    {
        $tables = DB::table('information_schema.tables')->where('table_schema', 'lab_equipment')
            ->orderBy('table_name')->pluck('table_name')->all();
        self::assertSame(['category', 'equipment', 'loan', 'loan_item', 'researcher', 'user'], $tables);
        $columns = DB::table('information_schema.columns')->where('table_schema', 'lab_equipment')
            ->orderBy('table_name')->orderBy('ordinal_position')->get();
        self::assertSame([
            'category' => ['id', 'name'],
            'equipment' => ['id', 'name', 'manufacturer', 'reference', 'category_id', 'total_units', 'available_units', 'cover_key', 'deleted_at'],
            'loan' => ['id', 'researcher_id', 'loaned_by_user_id', 'loaned_at'],
            'loan_item' => ['id', 'loan_id', 'equipment_id', 'equipment_name', 'reference', 'category_name', 'due_at', 'returned_at'],
            'researcher' => ['id', 'full_name', 'document_number'],
            'user' => ['id', 'username', 'password_hash', 'role'],
        ], $columns->groupBy('table_name')->map(fn ($items) => $items->pluck('column_name')->all())->all());
        foreach ($columns as $column) {
            self::assertNull($column->column_default, $column->column_name);
            self::assertNotContains($column->column_name, ['created_at', 'updated_at', 'status', 'days_late', 'version']);
        }
    }

    public function test_categories_match_the_five_fixed_identifiers(): void
    {
        self::assertSame([
            '11111111-1111-4111-8111-111111111111' => 'medición',
            '22222222-2222-4222-8222-222222222222' => 'óptica',
            '33333333-3333-4333-8333-333333333333' => 'electrónica',
            '44444444-4444-4444-8444-444444444444' => 'química',
            '55555555-5555-4555-8555-555555555555' => 'seguridad',
        ], DB::table('lab_equipment.category')->orderBy('id')->pluck('name', 'id')->all());
    }

    public function test_postgres_rejects_available_stock_above_total(): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('ck_equipment_available_range');
        DB::table('lab_equipment.equipment')->insert($this->equipment(['available_units' => 2]));
    }

    public function test_postgres_rejects_negative_available_stock(): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('ck_equipment_available_range');
        DB::table('lab_equipment.equipment')->insert($this->equipment(['available_units' => -1]));
    }

    public function test_active_reference_is_unique(): void
    {
        DB::table('lab_equipment.equipment')->insert($this->equipment());
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('uq_equipment_active_reference');
        DB::table('lab_equipment.equipment')->insert($this->equipment());
    }

    public function test_a_retired_reference_can_be_reused(): void
    {
        DB::table('lab_equipment.equipment')->insert($this->equipment(['deleted_at' => '2026-11-14T00:00:00Z']));
        DB::table('lab_equipment.equipment')->insert($this->equipment());
        self::assertSame(2, DB::table('lab_equipment.equipment')->where('reference', 'LAB-TEST')->count());
    }

    public function test_category_foreign_key_rejects_an_orphan(): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('equipment_category_id_fkey');
        DB::table('lab_equipment.equipment')->insert($this->equipment(['category_id' => (string) Str::uuid()]));
    }

    public function test_referenced_category_cannot_be_deleted(): void
    {
        DB::table('lab_equipment.equipment')->insert($this->equipment());
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('equipment_category_id_fkey');
        DB::table('lab_equipment.category')->where('id', '11111111-1111-4111-8111-111111111111')->delete();
    }

    private function equipment(array $overrides = []): array
    {
        return array_replace([
            'id' => (string) Str::uuid(), 'name' => 'Equipo de prueba', 'manufacturer' => 'Demostración',
            'reference' => 'LAB-TEST', 'category_id' => '11111111-1111-4111-8111-111111111111',
            'total_units' => 1, 'available_units' => 1, 'cover_key' => null, 'deleted_at' => null,
        ], $overrides);
    }
}
