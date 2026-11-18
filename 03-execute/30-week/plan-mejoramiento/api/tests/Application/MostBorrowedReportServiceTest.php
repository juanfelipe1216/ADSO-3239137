<?php

declare(strict_types=1);

namespace Tests\Application;

use DateTimeImmutable;
use Lab\Application\Services\MostBorrowedReportService;
use Lab\Domain\DomainViolation;
use Lab\Domain\Equipment;
use Lab\Domain\Loan;
use Lab\Domain\LoanItem;
use Lab\Domain\User;
use Lab\Domain\ValueObjects\Quantity;
use Lab\Domain\ValueObjects\Reference;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemoryLoanRepository;
use Tests\Fakes\InMemoryReportReader;

final class MostBorrowedReportServiceTest extends TestCase
{
    private InMemoryLoanRepository $loans;
    private InMemoryReportReader $reader;
    private MostBorrowedReportService $service;
    private User $admin;

    protected function setUp(): void
    {
        $this->loans = new InMemoryLoanRepository();
        $this->reader = new InMemoryReportReader($this->loans);
        $this->service = new MostBorrowedReportService($this->reader);
        $this->admin = new User('admin-private-id', 'private-admin', 'fixture-hash', 'admin');
    }

    private function equipment(string $id = 'a', string $name = 'Multímetro', string $category = 'medición'): Equipment
    {
        return Equipment::create($id, $name, 'Fabricante privado', new Reference('LAB-' . $id), 'category', $category, new Quantity(2));
    }

    private function lend(string $id, string $at, array $equipment): void
    {
        $instant = new DateTimeImmutable($at);
        $items = array_map(static fn (Equipment $item): LoanItem => LoanItem::borrow($id . '-' . $item->id, $item, $instant), $equipment);
        $this->loans->add(new Loan($id, 'private-researcher-' . $id, 'private-operator', $instant, $items));
    }

    public function test_empty_range_returns_exact_contract_without_extra_fields(): void
    {
        self::assertSame(['from' => '2026-11-18', 'to' => '2026-11-18', 'rows' => []], $this->service->execute($this->admin, '2026-11-18', '2026-11-18'));
    }

    public static function invalidRanges(): array
    {
        return [['', '2026-11-18', 'VALIDATION_ERROR'], ['2026-11-18', '', 'VALIDATION_ERROR'],
            ['2026-02-29', '2026-11-18', 'VALIDATION_ERROR'], ['18/11/2026', '2026-11-18', 'VALIDATION_ERROR'],
            ['2026-11-18', '2026-11-18T23:00:00Z', 'VALIDATION_ERROR'], ['2026-11-19', '2026-11-18', 'INVALID_DATE_RANGE']];
    }

    #[DataProvider('invalidRanges')]
    public function test_invalid_dates_never_reach_reader(string $from, string $to, string $code): void
    {
        $this->rejects($code, fn () => $this->service->execute($this->admin, $from, $to));
        self::assertSame(0, $this->reader->calls);
    }

    public function test_technician_is_denied_before_validation_or_reading(): void
    {
        $actor = new User('operator', 'operator', 'fixture-hash', 'lab_technician');
        foreach ([['2026-11-18', '2026-11-18'], ['', '']] as [$from, $to]) {
            $this->rejects('FORBIDDEN_ROLE', fn () => $this->service->execute($actor, $from, $to));
        }
        self::assertSame(0, $this->reader->calls);
    }

    public function test_both_utc_days_are_included_using_loan_date_not_due_or_return_date(): void
    {
        $equipment = $this->equipment();
        foreach (['before' => '2026-11-17T23:59:59.999999Z', 'start' => '2026-11-17T19:00:00-05:00',
            'end' => '2026-11-19T23:59:59.999999Z', 'after' => '2026-11-20T00:00:00Z'] as $id => $at) {
            $this->lend($id, $at, [$equipment]);
        }
        $this->loans->markReturned('start', 'start-a', new DateTimeImmutable('2026-11-24T00:00:00Z'));
        $result = $this->service->execute($this->admin, '2026-11-18', '2026-11-19');
        self::assertSame([['equipment_id' => 'a', 'equipment_name' => 'Multímetro', 'category_name' => 'medición', 'times_borrowed' => 2]], $result['rows']);
    }

    public function test_each_line_counts_and_returns_do_not_reduce_totals(): void
    {
        $this->lend('loan', '2026-11-18T12:00:00Z', [$this->equipment(), $this->equipment('b', 'Balanza')]);
        $before = $this->service->execute($this->admin, '2026-11-18', '2026-11-18');
        self::assertSame(['b', 'a'], array_column($before['rows'], 'equipment_id'));
        self::assertSame([1, 1], array_column($before['rows'], 'times_borrowed'));
        $this->loans->markReturned('loan', 'loan-a', new DateTimeImmutable('2026-11-19T00:00:00Z'));
        $this->loans->markReturned('loan', 'loan-b', new DateTimeImmutable('2026-11-19T00:00:00Z'));
        self::assertSame($before, $this->service->execute($this->admin, '2026-11-18', '2026-11-18'));
    }

    public function test_renaming_and_recategorizing_create_separate_historical_groups(): void
    {
        $equipment = $this->equipment();
        $this->lend('old', '2026-11-17T12:00:00Z', [$equipment]);
        $old = $this->service->execute($this->admin, '2026-11-17', '2026-11-17');
        $renamed = $equipment->revise('Osciloscopio', 'Otro fabricante', 'new-category', 'óptica', new Quantity(2));
        $this->lend('new', '2026-11-18T12:00:00Z', [$renamed]);
        $this->lend('category', '2026-11-18T13:00:00Z', [$renamed->revise('Osciloscopio', 'Otro', 'third-category', 'electrónica', new Quantity(2))]);
        self::assertSame($old, $this->service->execute($this->admin, '2026-11-17', '2026-11-17'));
        $rows = $this->service->execute($this->admin, '2026-11-17', '2026-11-18')['rows'];
        self::assertCount(3, $rows);
        self::assertSame(['Multímetro', 'Osciloscopio', 'Osciloscopio'], array_column($rows, 'equipment_name'));
        self::assertSame(['medición', 'electrónica', 'óptica'], array_column($rows, 'category_name'));
    }

    public function test_count_then_name_category_and_id_order_preserves_distinct_equipment(): void
    {
        foreach (['z', 'a'] as $id) {
            $this->lend('same-' . $id, '2026-11-18T00:00:00Z', [$this->equipment($id, '10')]);
        }
        $this->lend('number', '2026-11-18T00:00:00Z', [$this->equipment('b', '2')]);
        $this->lend('popular', '2026-11-18T00:00:00Z', [$this->equipment('c', 'Último')]);
        $this->lend('repeat', '2026-11-18T01:00:00Z', [$this->equipment('c', 'Último')]);
        $rows = $this->service->execute($this->admin, '2026-11-18', '2026-11-18')['rows'];
        self::assertSame(['c', 'a', 'z', 'b'], array_column($rows, 'equipment_id'));
        self::assertSame([2, 1, 1, 1], array_column($rows, 'times_borrowed'));
    }

    public function test_report_exposes_only_aggregate_fields_and_does_not_change_loans(): void
    {
        $this->lend('loan', '2026-11-18T00:00:00Z', [$this->equipment()]);
        $before = $this->loans->rows;
        $result = $this->service->execute($this->admin, '2026-11-18', '2026-11-18');
        self::assertSame(['from', 'to', 'rows'], array_keys($result));
        self::assertSame(['equipment_id', 'equipment_name', 'category_name', 'times_borrowed'], array_keys($result['rows'][0]));
        self::assertStringNotContainsString('private', json_encode($result, JSON_THROW_ON_ERROR));
        self::assertStringNotContainsString('Fabricante', json_encode($result, JSON_THROW_ON_ERROR));
        self::assertSame($before, $this->loans->rows);
    }

    private function rejects(string $code, callable $operation): void
    {
        try { $operation(); self::fail('Se aceptó una consulta inválida.'); }
        catch (DomainViolation $error) { self::assertSame($code, $error->errorCode); }
    }
}
