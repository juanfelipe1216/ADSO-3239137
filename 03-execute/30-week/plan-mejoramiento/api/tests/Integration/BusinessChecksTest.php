<?php

declare(strict_types=1);

namespace Tests\Integration;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class BusinessChecksTest extends TestCase
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

    public function test_zero_total_is_rejected_even_when_available_is_zero(): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('ck_equipment_total_positive');
        DB::table('lab_equipment.equipment')->insert($this->equipment(0));
    }

    public function test_update_cannot_reduce_total_to_zero(): void
    {
        $equipment = $this->equipment(1);
        DB::table('lab_equipment.equipment')->insert($equipment);
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('ck_equipment_total_positive');
        DB::table('lab_equipment.equipment')->where('id', $equipment['id'])->update(['total_units' => 0]);
    }

    public function test_positive_total_with_no_available_units_is_valid(): void
    {
        $equipment = $this->equipment(1);
        DB::table('lab_equipment.equipment')->insert($equipment);
        self::assertSame(0, DB::table('lab_equipment.equipment')->where('id', $equipment['id'])->value('available_units'));
    }

    #[DataProvider('invalidRoles')]
    public function test_unrecognized_roles_are_rejected_by_postgres(string $role): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('ck_user_role');
        DB::table('lab_equipment.user')->insert($this->user($role));
    }

    public static function invalidRoles(): array { return [['researcher'], ['ADMIN'], ['admin '], ['']]; }

    public function test_role_cannot_be_changed_to_an_unknown_value(): void
    {
        $user = $this->user('lab_technician');
        DB::table('lab_equipment.user')->insert($user);
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('ck_user_role');
        DB::table('lab_equipment.user')->where('id', $user['id'])->update(['role' => 'researcher']);
    }

    #[DataProvider('validRoles')]
    public function test_both_official_roles_are_accepted(string $role): void
    {
        $user = $this->user($role);
        DB::table('lab_equipment.user')->insert($user);
        self::assertSame($role, DB::table('lab_equipment.user')->where('id', $user['id'])->value('role'));
    }

    public static function validRoles(): array { return [['admin'], ['lab_technician']]; }

    public function test_checks_are_present_and_validated_in_the_postgres_catalog(): void
    {
        $checks = DB::select(<<<'SQL'
SELECT conname, convalidated, pg_get_constraintdef(c.oid) AS definition
FROM pg_constraint c JOIN pg_namespace n ON n.oid = c.connamespace
WHERE n.nspname = 'lab_equipment' AND conname IN ('ck_equipment_total_positive', 'ck_user_role')
ORDER BY conname
SQL);
        self::assertSame(['ck_equipment_total_positive', 'ck_user_role'], array_column($checks, 'conname'));
        foreach ($checks as $check) {
            self::assertTrue($check->convalidated);
        }
        self::assertStringContainsString('total_units >= 1', $checks[0]->definition);
        self::assertStringContainsString('lab_technician', $checks[1]->definition);
    }

    private function equipment(int $total): array
    {
        return ['id' => (string) Str::uuid(), 'name' => 'Equipo de prueba', 'manufacturer' => 'Demostración',
            'reference' => 'CHECK-TEST', 'category_id' => '11111111-1111-4111-8111-111111111111',
            'total_units' => $total, 'available_units' => 0, 'cover_key' => null, 'deleted_at' => null];
    }

    private function user(string $role): array
    {
        return ['id' => (string) Str::uuid(), 'username' => 'check-test', 'password_hash' => 'fixture-hash', 'role' => $role];
    }
}
