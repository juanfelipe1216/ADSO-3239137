<?php

declare(strict_types=1);

namespace Tests\Application;

use DateTimeImmutable;
use Lab\Application\DTOs\StoredEquipment;
use Lab\Application\Ports\IdGenerator;
use Lab\Application\Services\CreateEquipmentService;
use Lab\Application\Services\GetEquipmentService;
use Lab\Application\Services\ListCategoriesService;
use Lab\Application\Services\RetireEquipmentService;
use Lab\Application\Services\UpdateEquipmentService;
use Lab\Domain\DomainViolation;
use Lab\Domain\Equipment;
use Lab\Domain\User;
use Lab\Domain\ValueObjects\Quantity;
use Lab\Domain\ValueObjects\Reference;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\FrozenClock;
use Tests\Fakes\InMemoryCategories;
use Tests\Fakes\InMemoryEquipmentRepository;

final class EquipmentCatalogServiceTest extends TestCase
{
    private InMemoryEquipmentRepository $repository;
    private InMemoryCategories $categories;
    private CreateEquipmentService $create;
    private UpdateEquipmentService $update;
    private RetireEquipmentService $retire;
    private User $admin;

    protected function setUp(): void
    {
        $this->repository = new InMemoryEquipmentRepository();
        $this->categories = new InMemoryCategories(['category-1' => 'medición', 'category-2' => 'electrónica']);
        $ids = new class implements IdGenerator {
            private int $next = 0;
            public function generate(): string { return 'equipment-' . ++$this->next; }
        };
        $this->create = new CreateEquipmentService($this->repository, $this->categories, $ids);
        $this->update = new UpdateEquipmentService($this->repository, $this->categories);
        $this->retire = new RetireEquipmentService($this->repository, new FrozenClock(new DateTimeImmutable('2026-11-16T19:00:00-05:00')));
        $this->admin = new User('admin-id', 'admin', 'fixture-hash', 'admin');
    }

    private function createEquipment(): void
    {
        $this->create->execute($this->admin, ' Multímetro ', ' Fabricante ', ' lab-001 ', 'category-1', 3);
    }

    public function test_creation_initializes_stock_and_returns_only_contract_fields(): void
    {
        $this->createEquipment();
        $result = (new GetEquipmentService($this->repository))->execute('equipment-1')->jsonSerialize();
        self::assertSame(['id' => 'equipment-1', 'name' => 'Multímetro', 'manufacturer' => 'Fabricante', 'reference' => 'LAB-001',
            'category' => ['id' => 'category-1', 'name' => 'medición'], 'total_units' => 3, 'available_units' => 3, 'cover_key' => null], $result);
    }

    public function test_technician_cannot_create_edit_or_retire_even_missing_equipment(): void
    {
        $actor = new User('operator-id', 'operator', 'fixture-hash', 'lab_technician');
        $this->assertViolation('FORBIDDEN_ROLE', fn () => $this->create->execute($actor, '', '', '', 'missing', 0));
        $this->assertViolation('FORBIDDEN_ROLE', fn () => $this->update->execute($actor, 'missing', '', '', 'missing', 0));
        $this->assertViolation('FORBIDDEN_ROLE', fn () => $this->retire->execute($actor, 'missing'));
        self::assertSame([], $this->repository->lookups);
        self::assertSame([], $this->repository->rows);
    }

    public function test_unknown_category_is_rejected_without_creating_equipment(): void
    {
        $this->assertViolation('NOT_FOUND', fn () => $this->create->execute($this->admin, 'Equipo', 'Fabricante', 'A', 'missing', 1));
        self::assertSame([], $this->repository->rows);
    }

    public function test_active_duplicate_reference_is_rejected_and_retired_reference_can_be_reused(): void
    {
        $this->createEquipment();
        $this->assertViolation('REFERENCE_TAKEN', fn () => $this->create->execute($this->admin, 'Otro', 'Otro', ' lab-001 ', 'category-1', 1));
        self::assertCount(1, $this->repository->rows);
        $this->retire->execute($this->admin, 'equipment-1');
        $created = $this->create->execute($this->admin, 'Otro', 'Otro', 'lab-001', 'category-1', 1)->jsonSerialize();
        self::assertSame('LAB-001', $created['reference']);
        self::assertCount(2, $this->repository->rows);
        self::assertNotNull($this->repository->rows['equipment-1']->equipment->deletedAt);
    }

    public function test_insert_conflict_after_reference_lookup_is_propagated(): void
    {
        $this->repository->beforeAdd = static function (InMemoryEquipmentRepository $repository): void {
            $repository->add(Equipment::create('other-id', 'Otro', 'Otro', new Reference('LAB-001'), 'category-1', 'medición', new Quantity(1)));
        };
        $this->assertViolation('REFERENCE_TAKEN', fn () => $this->createEquipment());
        self::assertSame(['other-id'], array_keys($this->repository->rows));
    }

    public function test_edit_preserves_reference_photo_and_outstanding_units(): void
    {
        $this->createEquipment();
        $original = $this->repository->rows['equipment-1'];
        $this->repository->save($original->equipment->withdraw()->withCover('opaque-key'), $original->version);
        $result = $this->update->execute($this->admin, 'equipment-1', 'Osciloscopio', 'Otro', 'category-2', 5)->jsonSerialize();
        self::assertSame('LAB-001', $result['reference']);
        self::assertSame('opaque-key', $result['cover_key']);
        self::assertSame(4, $result['available_units']);
        self::assertSame(['id' => 'category-2', 'name' => 'electrónica'], $result['category']);
        self::assertSame('3', $this->repository->rows['equipment-1']->version);
    }

    public function test_invalid_total_does_not_save_any_metadata_changes(): void
    {
        $this->createEquipment();
        $original = $this->repository->rows['equipment-1'];
        $this->repository->save($original->equipment->withdraw()->withdraw(), $original->version);
        $borrowed = $this->repository->rows['equipment-1'];
        $this->assertViolation('VALIDATION_ERROR', fn () => $this->update->execute($this->admin, 'equipment-1', 'Otro', 'Otro', 'category-2', 1));
        self::assertSame($borrowed, $this->repository->rows['equipment-1']);
    }

    public function test_missing_category_during_edit_preserves_the_record(): void
    {
        $this->createEquipment();
        $before = $this->repository->rows;
        $this->assertViolation('NOT_FOUND', fn () => $this->update->execute($this->admin, 'equipment-1', 'Otro', 'Otro', 'missing', 4));
        self::assertSame($before, $this->repository->rows);
    }

    public function test_retirement_is_logical_and_invisible_to_detail_and_edit(): void
    {
        $this->createEquipment();
        $this->retire->execute($this->admin, 'equipment-1');
        self::assertSame('2026-11-17T00:00:00+00:00', $this->repository->rows['equipment-1']->equipment->deletedAt->format(DATE_ATOM));
        self::assertCount(1, $this->repository->rows);
        $this->assertViolation('NOT_FOUND', fn () => (new GetEquipmentService($this->repository))->execute('equipment-1'));
        $this->assertViolation('NOT_FOUND', fn () => $this->update->execute($this->admin, 'equipment-1', 'Otro', 'Otro', 'category-1', 4));
        $this->assertViolation('NOT_FOUND', fn () => $this->retire->execute($this->admin, 'equipment-1'));
    }

    public function test_equipment_with_pending_units_cannot_be_retired(): void
    {
        $this->createEquipment();
        $original = $this->repository->rows['equipment-1'];
        $this->repository->save($original->equipment->withdraw(), $original->version);
        $before = $this->repository->rows;
        $this->assertViolation('EQUIPMENT_HAS_OPEN_LOANS', fn () => $this->retire->execute($this->admin, 'equipment-1'));
        self::assertSame($before, $this->repository->rows);
    }

    #[DataProvider('competingOperations')]
    public function test_stale_edit_or_retirement_cannot_overwrite_a_loan(string $operation): void
    {
        $this->createEquipment();
        $this->repository->beforeSave = static function (InMemoryEquipmentRepository $repository): void {
            $current = $repository->rows['equipment-1'];
            $repository->rows['equipment-1'] = new StoredEquipment($current->equipment->withdraw(), '2');
        };
        $this->assertViolation('EQUIPMENT_NOT_AVAILABLE', fn () => $operation === 'edit'
            ? $this->update->execute($this->admin, 'equipment-1', 'Otro', 'Otro', 'category-1', 5)
            : $this->retire->execute($this->admin, 'equipment-1'));
        self::assertSame(2, $this->repository->rows['equipment-1']->equipment->availableUnits);
        self::assertSame('Multímetro', $this->repository->rows['equipment-1']->equipment->name);
        self::assertNull($this->repository->rows['equipment-1']->equipment->deletedAt);
    }

    public static function competingOperations(): array { return [['edit'], ['retire']]; }

    public function test_missing_equipment_is_not_found(): void
    {
        $this->assertViolation('NOT_FOUND', fn () => (new GetEquipmentService($this->repository))->execute('missing'));
    }

    public function test_categories_are_listed_in_name_order_without_pagination(): void
    {
        self::assertSame(['items' => [
            ['id' => 'category-2', 'name' => 'electrónica'], ['id' => 'category-1', 'name' => 'medición'],
        ]], (new ListCategoriesService($this->categories))->execute());
    }

    private function assertViolation(string $code, callable $operation): void
    {
        try {
            $operation();
            self::fail('Se aceptó una operación que debía rechazarse.');
        } catch (DomainViolation $error) {
            self::assertSame($code, $error->errorCode);
        }
    }
}
