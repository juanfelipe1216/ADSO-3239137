<?php

declare(strict_types=1);

namespace Tests\Unit;

use DateTimeImmutable;
use Lab\Domain\DomainViolation;
use Lab\Domain\Equipment;
use Lab\Domain\ValueObjects\Quantity;
use Lab\Domain\ValueObjects\Reference;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EquipmentTest extends TestCase
{
    private function equipment(): Equipment
    {
        return Equipment::create('equipment-1', ' Multímetro ', ' Fabricante ', new Reference('lab-001'),
            'category-1', 'electrónica', new Quantity(3));
    }

    public function test_creation_and_stock_transitions_preserve_the_original(): void
    {
        $original = $this->equipment();
        $borrowed = $original->withdraw()->withdraw();
        self::assertSame('Multímetro', $original->name);
        self::assertSame('Fabricante', $original->manufacturer);
        self::assertSame(3, $original->availableUnits);
        self::assertSame(1, $borrowed->availableUnits);
        self::assertSame(2, $borrowed->restock()->availableUnits);
    }

    public function test_last_unit_can_be_lent_only_once(): void
    {
        $empty = $this->equipment()->withdraw()->withdraw()->withdraw();
        try {
            $empty->withdraw();
            self::fail('Se permitió prestar sin existencias.');
        } catch (DomainViolation $error) {
            self::assertSame('EQUIPMENT_NOT_AVAILABLE', $error->errorCode);
            self::assertSame(0, $empty->availableUnits);
        }
    }

    public function test_return_cannot_exceed_total(): void
    {
        $this->expectException(DomainViolation::class);
        $this->equipment()->restock();
    }

    public function test_total_changes_keep_borrowed_count(): void
    {
        $equipment = $this->equipment()->withdraw()->withdraw();
        self::assertSame(3, $equipment->changeTotal(new Quantity(5))->availableUnits);
        self::assertSame(0, $equipment->changeTotal(new Quantity(2))->availableUnits);
        $this->expectException(DomainViolation::class);
        $equipment->changeTotal(new Quantity(1));
    }

    public function test_failed_edit_leaves_metadata_and_stock_unchanged(): void
    {
        $equipment = $this->equipment()->withdraw()->withdraw();
        try {
            $equipment->revise('Nuevo', 'Otro', 'category-2', 'óptica', new Quantity(1));
            self::fail('Se permitió reducir el total por debajo de lo prestado.');
        } catch (DomainViolation $error) {
            self::assertSame('VALIDATION_ERROR', $error->errorCode);
            self::assertSame('Multímetro', $equipment->name);
            self::assertSame(3, $equipment->totalUnits->value);
            self::assertSame(1, $equipment->availableUnits);
        }
    }

    public function test_edit_preserves_reference(): void
    {
        $equipment = $this->equipment();
        $edited = $equipment->revise('Osciloscopio', 'Otro', 'category-2', 'medición', new Quantity(5));
        self::assertSame($equipment->reference, $edited->reference);
        self::assertSame('Osciloscopio', $edited->name);
        self::assertSame(5, $edited->availableUnits);
    }

    public function test_retirement_requires_all_units_back(): void
    {
        try {
            $this->equipment()->withdraw()->retire(new DateTimeImmutable('2026-11-15T19:00:00-05:00'));
            self::fail('Se permitió dar de baja un equipo prestado.');
        } catch (DomainViolation $error) {
            self::assertSame('EQUIPMENT_HAS_OPEN_LOANS', $error->errorCode);
        }
        $retired = $this->equipment()->retire(new DateTimeImmutable('2026-11-15T19:00:00-05:00'));
        self::assertSame('2026-11-16T00:00:00+00:00', $retired->deletedAt->format(DATE_ATOM));
        $this->expectException(DomainViolation::class);
        $retired->withdraw();
    }

    #[DataProvider('invalidStock')]
    public function test_invalid_restored_stock_is_rejected(int $available): void
    {
        $this->expectException(DomainViolation::class);
        new Equipment('id', 'Nombre', 'Fabricante', new Reference('A'), 'category', 'medición', new Quantity(3), $available);
    }

    public static function invalidStock(): array { return [[-1], [4]]; }

    #[DataProvider('invalidMetadata')]
    public function test_invalid_metadata_is_rejected(string $name, string $manufacturer): void
    {
        $this->expectException(DomainViolation::class);
        $this->equipment()->revise($name, $manufacturer, 'category', 'medición', new Quantity(3));
    }

    public static function invalidMetadata(): array
    {
        return [[' ', 'Fabricante'], [str_repeat('á', 251), 'Fabricante'], ['Nombre', ' '], ['Nombre', str_repeat('x', 201)]];
    }

    public function test_cover_can_be_replaced_and_removed(): void
    {
        $equipment = $this->equipment()->withCover('opaque-key_123');
        self::assertSame('opaque-key_123', $equipment->coverKey);
        self::assertNull($equipment->withCover(null)->coverKey);
        $this->expectException(DomainViolation::class);
        $equipment->withCover('../private/file.jpg');
    }
}
