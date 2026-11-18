<?php

declare(strict_types=1);

namespace Tests\Application;

use DateTimeImmutable;
use Lab\Application\DTOs\OverdueLine;
use Lab\Application\Ports\Clock;
use Lab\Application\Ports\OverdueReader;
use Lab\Application\Services\ListOverdueService;
use Lab\Application\Services\ReturnItemService;
use Lab\Domain\Equipment;
use Lab\Domain\Loan;
use Lab\Domain\LoanItem;
use Lab\Domain\Researcher;
use Lab\Domain\ValueObjects\DocumentNumber;
use Lab\Domain\ValueObjects\Quantity;
use Lab\Domain\ValueObjects\Reference;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\FrozenClock;
use Tests\Fakes\InMemoryEquipmentRepository;
use Tests\Fakes\InMemoryLoanRepository;
use Tests\Fakes\InMemoryOverdueReader;
use Tests\Fakes\InMemoryResearcherReader;
use Tests\Fakes\InMemoryTransactionManager;

final class ListOverdueServiceTest extends TestCase
{
    private InMemoryLoanRepository $loans;
    private InMemoryEquipmentRepository $equipment;
    private InMemoryResearcherReader $researchers;
    private InMemoryOverdueReader $reader;
    private FrozenClock $clock;
    private ListOverdueService $service;

    protected function setUp(): void
    {
        $this->loans = new InMemoryLoanRepository();
        $this->equipment = new InMemoryEquipmentRepository();
        $this->researchers = new InMemoryResearcherReader();
        $this->reader = new InMemoryOverdueReader($this->loans, $this->researchers);
        $this->clock = new FrozenClock(new DateTimeImmutable('2026-11-17T19:00:00-05:00'));
        $this->service = new ListOverdueService($this->reader, $this->clock);
    }

    private function seed(string $loanId, string $dueAt, array $itemIds, array $returnedIds = []): void
    {
        $researcherId = 'researcher-' . $loanId;
        $this->researchers->researchers[] = new Researcher($researcherId, 'Investigador ' . $loanId, new DocumentNumber($loanId));
        $at = (new DateTimeImmutable($dueAt))->modify('-5 days');
        $items = [];
        foreach ($itemIds as $id) {
            $equipment = Equipment::create('equipment-' . $id, 'Equipo ' . $id, 'Fabricante', new Reference('LAB-' . $id),
                'category-1', 'medición', new Quantity(1));
            $item = LoanItem::borrow($id, $equipment, $at);
            if (in_array($id, $returnedIds, true)) {
                $item = $item->returnAt($at->modify('+1 day'));
            } else {
                $equipment = $equipment->withdraw();
            }
            $items[] = $item;
            $this->equipment->add($equipment);
        }
        $this->loans->add(new Loan($loanId, $researcherId, 'operator', $at, $items));
    }

    public function test_empty_result_has_exact_contract_shape(): void
    {
        self::assertSame(['items' => []], $this->service->execute());
    }

    public function test_only_pending_overdue_lines_are_listed_in_due_time_then_item_id_order(): void
    {
        $this->seed('recent', '2026-11-17T23:59:59Z', ['recent']);
        $this->seed('returned', '2026-11-10T00:00:00Z', ['returned'], ['returned']);
        $this->seed('old', '2026-11-15T00:00:00Z', ['z', 'a']);
        $this->seed('exact', '2026-11-18T00:00:00Z', ['exact']);
        $this->seed('future', '2026-11-18T00:00:01Z', ['future']);
        $this->seed('partial', '2026-11-16T00:00:00Z', ['m', 'n'], ['n']);
        $beforeLoans = $this->loans->rows;
        $beforeEquipment = $this->equipment->rows;
        self::assertSame(['items' => [
            ['loan_id' => 'old', 'item_id' => 'a', 'equipment_name' => 'Equipo a', 'researcher_name' => 'Investigador old', 'due_at' => '2026-11-15T00:00:00Z', 'days_late' => 3],
            ['loan_id' => 'old', 'item_id' => 'z', 'equipment_name' => 'Equipo z', 'researcher_name' => 'Investigador old', 'due_at' => '2026-11-15T00:00:00Z', 'days_late' => 3],
            ['loan_id' => 'partial', 'item_id' => 'm', 'equipment_name' => 'Equipo m', 'researcher_name' => 'Investigador partial', 'due_at' => '2026-11-16T00:00:00Z', 'days_late' => 2],
            ['loan_id' => 'recent', 'item_id' => 'recent', 'equipment_name' => 'Equipo recent', 'researcher_name' => 'Investigador recent', 'due_at' => '2026-11-17T23:59:59Z', 'days_late' => 0],
        ]], $this->service->execute());
        self::assertSame($beforeLoans, $this->loans->rows);
        self::assertSame($beforeEquipment, $this->equipment->rows);
    }

    public function test_fractional_instants_determine_inclusion_order_and_full_days(): void
    {
        $this->seed('earlier', '2026-11-16T19:00:00.900000-05:00', ['z-earlier']);
        $this->seed('later', '2026-11-17T00:00:00.950000Z', ['a-later']);
        $this->seed('boundary', '2026-11-18T00:00:00.100000Z', ['at-boundary']);
        $clock = new FrozenClock(new DateTimeImmutable('2026-11-18T00:00:00.100000Z'));
        $rows = (new ListOverdueService($this->reader, $clock))->execute()['items'];
        self::assertSame(['z-earlier', 'a-later'], array_column($rows, 'item_id'));
        self::assertSame([0, 0], array_column($rows, 'days_late'));
        self::assertSame('2026-11-17T00:00:00Z', $rows[0]['due_at']);
    }

    public function test_equipment_name_is_frozen_after_catalog_rename(): void
    {
        $this->seed('old', '2026-11-15T00:00:00Z', ['a']);
        $equipment = $this->equipment->findActive('equipment-a');
        $this->equipment->save($equipment->equipment->revise('Nombre nuevo', 'Otro fabricante', 'category-2', 'óptica', new Quantity(2)), $equipment->version);
        $rows = $this->service->execute()['items'];
        self::assertSame('Equipo a', $rows[0]['equipment_name']);
        self::assertSame('Investigador old', $rows[0]['researcher_name']);
        self::assertSame(['loan_id', 'item_id', 'equipment_name', 'researcher_name', 'due_at', 'days_late'], array_keys($rows[0]));
    }

    public function test_returned_line_disappears_and_remaining_overdue_line_stays(): void
    {
        $this->seed('old', '2026-11-15T00:00:00Z', ['a', 'b']);
        $return = new ReturnItemService($this->loans, $this->equipment,
            new InMemoryTransactionManager($this->equipment, $this->loans), $this->clock);
        self::assertCount(2, $this->service->execute()['items']);
        $return->execute('old', 'a');
        self::assertSame(['b'], array_column($this->service->execute()['items'], 'item_id'));
        $return->execute('old', 'b');
        self::assertSame(['items' => []], $this->service->execute());
        self::assertCount(1, $this->loans->rows);
        self::assertSame('closed', $this->loans->findById('old')->status($this->clock->now()));
    }

    public function test_advancing_clock_changes_overdue_result_without_storing_status_or_days(): void
    {
        $this->seed('boundary', '2026-11-18T00:00:00Z', ['a']);
        $before = $this->loans->rows;
        self::assertSame(['items' => []], $this->service->execute());
        $later = new ListOverdueService($this->reader, new FrozenClock($this->clock->now()->modify('+1 second')));
        self::assertSame(0, $later->execute()['items'][0]['days_late']);
        $later = new ListOverdueService($this->reader, new FrozenClock($this->clock->now()->modify('+1 day')));
        self::assertSame(1, $later->execute()['items'][0]['days_late']);
        self::assertSame($before, $this->loans->rows);
    }

    public function test_one_utc_clock_read_is_shared_by_selection_and_day_calculation(): void
    {
        $this->seed('old', '2026-11-17T00:00:00Z', ['a']);
        $line = new OverdueLine('old', 'Investigador old', $this->loans->findById('old')->items[0]);
        $reader = new class($line) implements OverdueReader {
            public ?DateTimeImmutable $seen = null;
            public function __construct(private OverdueLine $line) {}
            public function findOverdue(DateTimeImmutable $now): array { $this->seen = $now; return [$this->line]; }
        };
        $clock = new class implements Clock {
            public int $calls = 0;
            public function now(): DateTimeImmutable {
                $this->calls++;
                return new DateTimeImmutable($this->calls === 1 ? '2026-11-17T19:00:00-05:00' : '2026-11-20T00:00:00Z');
            }
        };
        $result = (new ListOverdueService($reader, $clock))->execute();
        self::assertSame(1, $clock->calls);
        self::assertSame('UTC', $reader->seen->getTimezone()->getName());
        self::assertSame('2026-11-18T00:00:00+00:00', $reader->seen->format(DATE_ATOM));
        self::assertSame(1, $result['items'][0]['days_late']);
    }
}
