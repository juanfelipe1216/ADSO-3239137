<?php

declare(strict_types=1);

namespace Tests\Unit;

use DateTimeImmutable;
use Lab\Domain\DomainViolation;
use Lab\Domain\Equipment;
use Lab\Domain\LoanItem;
use Lab\Domain\ValueObjects\Quantity;
use Lab\Domain\ValueObjects\Reference;
use PHPUnit\Framework\TestCase;

final class LoanItemTest extends TestCase
{
    private function equipment(): Equipment
    {
        return Equipment::create('equipment-1', 'Multímetro', 'Fabricante', new Reference('lab-001'),
            'category-1', 'electrónica', new Quantity(2));
    }

    public function test_due_date_is_five_utc_days_even_across_daylight_saving(): void
    {
        $at = new DateTimeImmutable('2026-10-30 19:00:00', new \DateTimeZone('America/New_York'));
        $item = LoanItem::borrow('item-1', $this->equipment(), $at);
        self::assertSame('2026-11-04T23:00:00+00:00', $item->dueAt->format(DATE_ATOM));
        self::assertSame(5 * 86400, $item->dueAt->getTimestamp() - $at->getTimestamp());
    }

    public function test_snapshots_do_not_follow_catalog_changes(): void
    {
        $equipment = $this->equipment();
        $item = LoanItem::borrow('item-1', $equipment, new DateTimeImmutable('2026-11-15T23:00:00Z'));
        $changed = $equipment->revise('Osciloscopio', 'Nuevo', 'category-2', 'medición', new Quantity(3));
        self::assertSame('Osciloscopio', $changed->name);
        self::assertSame('Multímetro', $item->equipmentName);
        self::assertSame('electrónica', $item->categoryName);
        self::assertSame('LAB-001', $item->reference->value);
        self::assertSame('equipment-1', $item->equipmentId);
    }

    public function test_due_boundary_and_complete_days_late(): void
    {
        $item = LoanItem::borrow('item-1', $this->equipment(), new DateTimeImmutable('2026-11-15T23:00:00Z'));
        self::assertFalse($item->isOverdue($item->dueAt));
        self::assertSame(0, $item->daysLate($item->dueAt->modify('-1 second')));
        self::assertTrue($item->isOverdue($item->dueAt->modify('+1 second')));
        self::assertSame(0, $item->daysLate($item->dueAt->modify('+86399 seconds')));
        self::assertSame(1, $item->daysLate($item->dueAt->modify('+86400 seconds')));
        self::assertSame(2, $item->daysLate($item->dueAt->modify('+2 days 1 hour')));
    }

    public function test_return_is_utc_and_cannot_be_repeated(): void
    {
        $item = LoanItem::borrow('item-1', $this->equipment(), new DateTimeImmutable('2026-11-15T23:00:00Z'));
        $returned = $item->returnAt(new DateTimeImmutable('2026-11-21T19:00:00-05:00'));
        self::assertSame('2026-11-22T00:00:00+00:00', $returned->returnedAt->format(DATE_ATOM));
        self::assertFalse($returned->isOverdue(new DateTimeImmutable('2026-11-25T23:00:00Z')));
        self::assertSame(0, $returned->daysLate(new DateTimeImmutable('2026-11-25T23:00:00Z')));
        self::assertNull($item->returnedAt);
        try {
            $returned->returnAt(new DateTimeImmutable('2026-11-23T23:00:00Z'));
            self::fail('Se permitió una segunda devolución.');
        } catch (DomainViolation $error) {
            self::assertSame('ITEM_ALREADY_RETURNED', $error->errorCode);
        }
    }

    public function test_return_cannot_precede_the_loan(): void
    {
        $at = new DateTimeImmutable('2026-11-15T23:00:00Z');
        $item = LoanItem::borrow('item-1', $this->equipment(), $at);
        $this->expectException(DomainViolation::class);
        $item->returnAt($at->modify('-1 second'));
    }

    public function test_retired_equipment_cannot_be_added(): void
    {
        $at = new DateTimeImmutable('2026-11-15T23:00:00Z');
        $this->expectException(DomainViolation::class);
        LoanItem::borrow('item-1', $this->equipment()->retire($at), $at);
    }
}
