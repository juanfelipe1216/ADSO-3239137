<?php

declare(strict_types=1);

namespace Tests\Unit;

use DateTimeImmutable;
use Lab\Domain\DomainViolation;
use Lab\Domain\Loan;
use Lab\Domain\LoanItem;
use Lab\Domain\ValueObjects\Reference;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LoanTest extends TestCase
{
    private function item(string $id, string $equipmentId): LoanItem
    {
        return new LoanItem($id, $equipmentId, 'Multímetro', new Reference('lab-001'), 'electrónica',
            new DateTimeImmutable('2026-11-20T23:00:00Z'));
    }

    private function loan(array $items): Loan
    {
        return new Loan('loan-1', 'researcher-1', 'user-1', new DateTimeImmutable('2026-11-15T18:00:00-05:00'), $items);
    }

    public function test_partial_return_and_calculated_states(): void
    {
        $loan = $this->loan([$this->item('item-1', 'equipment-1'), $this->item('item-2', 'equipment-2')]);
        $due = new DateTimeImmutable('2026-11-20T23:00:00Z');
        self::assertSame('2026-11-15T23:00:00+00:00', $loan->loanedAt->format(DATE_ATOM));
        self::assertSame('open', $loan->status($due));
        self::assertSame('overdue', $loan->status($due->modify('+1 second')));
        $partial = $loan->returnItem('item-1', $due);
        self::assertSame('open', $partial->status($due));
        self::assertSame('overdue', $partial->status($due->modify('+1 second')));
        $closed = $partial->returnItem('item-2', $due->modify('+1 day'));
        self::assertSame('closed', $closed->status($due->modify('+2 days')));
        self::assertNull($loan->items[0]->returnedAt);
        self::assertNull($partial->items[1]->returnedAt);
    }

    public function test_one_line_loan_closes_on_its_return(): void
    {
        $loan = $this->loan([$this->item('item-1', 'equipment-1')]);
        $at = new DateTimeImmutable('2026-11-16T23:00:00Z');
        self::assertSame('closed', $loan->returnItem('item-1', $at)->status($at));
    }

    #[DataProvider('invalidCounts')]
    public function test_only_one_or_two_lines_are_allowed(int $count): void
    {
        $items = [];
        for ($index = 0; $index < $count; $index++) {
            $items[] = $this->item("item-{$index}", "equipment-{$index}");
        }
        $this->expectException(DomainViolation::class);
        $this->loan($items);
    }

    public static function invalidCounts(): array { return [[0], [3]]; }

    public function test_duplicate_equipment_has_specific_error(): void
    {
        try {
            $this->loan([$this->item('item-1', 'equipment-1'), $this->item('item-2', 'equipment-1')]);
            self::fail('Se permitió repetir un equipo.');
        } catch (DomainViolation $error) {
            self::assertSame('DUPLICATE_EQUIPMENT_IN_LOAN', $error->errorCode);
        }
    }

    public function test_duplicate_item_ids_are_rejected(): void
    {
        $this->expectException(DomainViolation::class);
        $this->loan([$this->item('item-1', 'equipment-1'), $this->item('item-1', 'equipment-2')]);
    }

    public function test_due_date_cannot_be_changed_when_restoring_a_loan(): void
    {
        $item = new LoanItem('item-1', 'equipment-1', 'Nombre', new Reference('A'), 'medición',
            new DateTimeImmutable('2026-11-21T23:00:00Z'));
        $this->expectException(DomainViolation::class);
        $this->loan([$item]);
    }

    public function test_returning_another_loans_item_is_not_found(): void
    {
        $loan = $this->loan([$this->item('item-1', 'equipment-1')]);
        try {
            $loan->returnItem('another-item', new DateTimeImmutable('2026-11-16T23:00:00Z'));
            self::fail('Se aceptó una línea ajena.');
        } catch (DomainViolation $error) {
            self::assertSame('NOT_FOUND', $error->errorCode);
            self::assertNull($loan->items[0]->returnedAt);
        }
    }
}
