<?php

declare(strict_types=1);

namespace Tests\Application;

use DateTimeImmutable;
use Lab\Application\Ports\Clock;
use Lab\Application\Ports\IdGenerator;
use Lab\Application\Services\LendEquipmentService;
use Lab\Domain\DomainViolation;
use Lab\Domain\Equipment;
use Lab\Domain\Loan;
use Lab\Domain\LoanItem;
use Lab\Domain\Researcher;
use Lab\Domain\User;
use Lab\Domain\ValueObjects\DocumentNumber;
use Lab\Domain\ValueObjects\Quantity;
use Lab\Domain\ValueObjects\Reference;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\FrozenClock;
use Tests\Fakes\InMemoryEquipmentRepository;
use Tests\Fakes\InMemoryLoanRepository;
use Tests\Fakes\InMemoryResearcherReader;
use Tests\Fakes\InMemoryTransactionManager;

final class LendEquipmentServiceTest extends TestCase
{
    private InMemoryEquipmentRepository $equipment;
    private InMemoryLoanRepository $loans;
    private InMemoryResearcherReader $researchers;
    private InMemoryTransactionManager $transactions;
    private IdGenerator $ids;
    private User $actor;
    private DateTimeImmutable $now;
    private LendEquipmentService $service;
    private int $seedSequence = 0;

    protected function setUp(): void
    {
        $this->equipment = new InMemoryEquipmentRepository();
        foreach (['a', 'b', 'c'] as $id) {
            $this->equipment->add(Equipment::create($id, 'Equipo ' . $id, 'Fabricante', new Reference('LAB-' . $id),
                'category-1', 'medición', new Quantity(3)));
        }
        $this->loans = new InMemoryLoanRepository();
        $this->researchers = new InMemoryResearcherReader([
            new Researcher('r-1', 'Investigador de prueba', new DocumentNumber('001')),
            new Researcher('r-2', 'Otro investigador', new DocumentNumber('002')),
        ]);
        $this->transactions = new InMemoryTransactionManager($this->equipment, $this->loans);
        $this->ids = new class implements IdGenerator {
            private int $next = 0;
            public function generate(): string { return 'generated-' . ++$this->next; }
        };
        $this->actor = new User('operator-id', 'operator', 'fixture-hash', 'lab_technician');
        $this->now = new DateTimeImmutable('2026-11-17T19:00:00-05:00');
        $this->service = $this->serviceWithClock(new FrozenClock($this->now));
    }

    private function serviceWithClock(Clock $clock): LendEquipmentService
    {
        return new LendEquipmentService($this->equipment, $this->researchers, $this->loans,
            $this->transactions, $clock, $this->ids);
    }

    private function seedLoan(int $count, string $at, string $researcher = 'r-1', bool $returned = false): Loan
    {
        $id = 'seed-' . ++$this->seedSequence;
        $items = [];
        $instant = new DateTimeImmutable($at);
        foreach (array_slice(['a', 'b'], 0, $count) as $equipmentId) {
            $stored = $this->equipment->findActive($equipmentId);
            $item = LoanItem::borrow($id . '-' . $equipmentId, $stored->equipment, $instant);
            if ($returned) {
                $item = $item->returnAt($instant->modify('+1 day'));
            } else {
                $this->equipment->save($stored->equipment->withdraw(), $stored->version);
            }
            $items[] = $item;
        }
        $loan = new Loan($id, $researcher, 'previous-operator', $instant, $items);
        $this->loans->add($loan);
        return $loan;
    }

    public static function rolesAndSizes(): array
    {
        return [['admin', ['a']], ['admin', ['b', 'a']], ['lab_technician', ['a']], ['lab_technician', ['b', 'a']]];
    }

    #[DataProvider('rolesAndSizes')]
    public function test_both_roles_lend_one_or_two_units_with_frozen_data_and_server_time(string $role, array $items): void
    {
        $actor = new User('authenticated-id', 'operator', 'fixture-hash', $role);
        $result = $this->service->execute($actor, 'r-1', $items)->jsonSerialize();
        $loan = $this->loans->rows[$result['id']];
        self::assertSame('authenticated-id', $loan->loanedByUserId);
        self::assertSame(['id', 'researcher_id', 'loaned_at', 'status', 'items'], array_keys($result));
        self::assertSame('r-1', $result['researcher_id']);
        self::assertSame('2026-11-18T00:00:00Z', $result['loaned_at']);
        self::assertSame('open', $result['status']);
        self::assertSame($items, array_column($result['items'], 'equipment_id'));
        foreach ($result['items'] as $line) {
            self::assertSame(['id', 'equipment_id', 'equipment_name', 'reference', 'category_name', 'due_at', 'returned_at'], array_keys($line));
            self::assertSame('Equipo ' . $line['equipment_id'], $line['equipment_name']);
            self::assertSame('LAB-' . strtoupper($line['equipment_id']), $line['reference']);
            self::assertSame('medición', $line['category_name']);
            self::assertSame('2026-11-23T00:00:00Z', $line['due_at']);
            self::assertNull($line['returned_at']);
            self::assertSame(2, $this->equipment->findActive($line['equipment_id'])->equipment->availableUnits);
        }
        self::assertCount(count($items) + 1, array_unique([$loan->id, ...array_column($result['items'], 'id')]));
        self::assertSame(3, $this->equipment->findActive('c')->equipment->availableUnits);
        self::assertSame(['begin:r-1', 'commit'], $this->transactions->events);
        self::assertNull($this->transactions->lockedResearcher);
    }

    public static function invalidRequests(): array
    {
        return [
            ['r-1', [], 'VALIDATION_ERROR'], ['r-1', ['a', 'b', 'c'], 'VALIDATION_ERROR'],
            ['', ['a'], 'VALIDATION_ERROR'], ['r-1', [''], 'VALIDATION_ERROR'],
            ['r-1', [null], 'VALIDATION_ERROR'], ['r-1', [42], 'VALIDATION_ERROR'],
            ['r-1', [['equipment_id' => 'a']], 'VALIDATION_ERROR'],
            ['r-1', ['equipment_id' => 'a'], 'VALIDATION_ERROR'],
            ['missing', ['a', 'a'], 'DUPLICATE_EQUIPMENT_IN_LOAN'],
            ['r-1', ['AAAAAAAA-AAAA-4AAA-8AAA-AAAAAAAAAAAA', 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa'], 'DUPLICATE_EQUIPMENT_IN_LOAN'],
        ];
    }

    #[DataProvider('invalidRequests')]
    public function test_invalid_structure_and_duplicates_are_rejected_before_reads(string $researcher, array $items, string $code): void
    {
        $this->rejectsUnchanged($code, fn () => $this->service->execute($this->actor, $researcher, $items));
        self::assertSame([], $this->equipment->lookups);
        self::assertSame([], $this->transactions->events);
    }

    public function test_missing_researcher_is_rejected_before_equipment_lookup(): void
    {
        $this->rejectsUnchanged('NOT_FOUND', fn () => $this->service->execute($this->actor, 'missing', ['a']));
        self::assertSame([], $this->equipment->lookups);
    }

    public function test_missing_or_retired_equipment_takes_precedence_over_overdue_and_stock(): void
    {
        $this->seedLoan(1, '2026-11-01T00:00:00Z');
        $b = $this->equipment->findActive('b');
        $this->equipment->save($b->equipment->retire($this->now), $b->version);
        $a = $this->equipment->findActive('a');
        $this->equipment->save($a->equipment->withdraw()->withdraw(), $a->version);
        foreach (['missing', 'b'] as $id) {
            $this->rejectsUnchanged('NOT_FOUND', fn () => $this->service->execute($this->actor, 'r-1', ['a', $id]));
        }
    }

    public function test_overdue_takes_precedence_over_limit(): void
    {
        $this->seedLoan(2, '2026-11-01T00:00:00Z');
        $this->rejectsUnchanged('RESEARCHER_HAS_OVERDUE', fn () => $this->service->execute($this->actor, 'r-1', ['c']));
    }

    public static function pendingCounts(): array { return [[1, ['b', 'c']], [2, ['c']]]; }

    #[DataProvider('pendingCounts')]
    public function test_limit_counts_lines_and_exposes_current_count_before_stock(int $count, array $items): void
    {
        $this->seedLoan($count, '2026-11-17T00:00:00Z');
        $c = $this->equipment->findActive('c');
        $this->equipment->save($c->equipment->withdraw()->withdraw()->withdraw(), $c->version);
        $error = $this->rejectsUnchanged('RESEARCHER_LIMIT_EXCEEDED', fn () => $this->service->execute($this->actor, 'r-1', $items));
        self::assertSame(['active_loans' => $count], $error->context);
    }

    public function test_two_prior_loans_are_counted_together_and_third_is_rejected(): void
    {
        $this->service->execute($this->actor, 'r-1', ['a']);
        $this->service->execute($this->actor, 'r-1', ['b']);
        $error = $this->rejectsUnchanged('RESEARCHER_LIMIT_EXCEEDED', fn () => $this->service->execute($this->actor, 'r-1', ['c']));
        self::assertSame(2, $error->context['active_loans']);
        self::assertCount(2, $this->loans->rows);
    }

    public static function dueBoundaries(): array
    {
        return [['2026-11-13T00:00:00Z', false], ['2026-11-13T00:00:01Z', false], ['2026-11-12T23:59:59Z', true]];
    }

    #[DataProvider('dueBoundaries')]
    public function test_exact_due_time_is_allowed_but_one_second_late_blocks(string $at, bool $overdue): void
    {
        $this->seedLoan(1, $at);
        if ($overdue) {
            $this->rejectsUnchanged('RESEARCHER_HAS_OVERDUE', fn () => $this->service->execute($this->actor, 'r-1', ['b']));
        } else {
            $this->service->execute($this->actor, 'r-1', ['b']);
            self::assertCount(2, $this->loans->pendingItemsForResearcher('r-1'));
        }
    }

    public function test_returned_lines_and_other_researchers_do_not_block_new_loans(): void
    {
        $this->seedLoan(2, '2026-11-01T00:00:00Z', returned: true);
        $this->seedLoan(2, '2026-11-01T00:00:00Z', 'r-2');
        $this->service->execute($this->actor, 'r-1', ['a', 'b']);
        self::assertCount(2, $this->loans->pendingItemsForResearcher('r-1'));
        self::assertCount(3, $this->loans->rows);
    }

    public function test_no_stock_in_second_equipment_preserves_both_equipment_and_loans(): void
    {
        $b = $this->equipment->findActive('b');
        $this->equipment->save($b->equipment->withdraw()->withdraw()->withdraw(), $b->version);
        $this->rejectsUnchanged('EQUIPMENT_NOT_AVAILABLE', fn () => $this->service->execute($this->actor, 'r-1', ['a', 'b']));
    }

    public function test_snapshots_survive_later_catalog_edits(): void
    {
        $result = $this->service->execute($this->actor, 'r-1', ['a']);
        $before = $result->jsonSerialize();
        $a = $this->equipment->findActive('a');
        $this->equipment->save($a->equipment->revise('Otro nombre', 'Otro fabricante', 'category-2', 'óptica', new Quantity(4)), $a->version);
        self::assertSame($before, $result->jsonSerialize());
        self::assertSame('Equipo a', $this->loans->rows[$before['id']]->items[0]->equipmentName);
        self::assertSame('medición', $this->loans->rows[$before['id']]->items[0]->categoryName);
    }

    public function test_clock_and_eligibility_are_read_after_acquiring_researcher_lock(): void
    {
        $clock = new class($this->transactions, $this->now) implements Clock {
            public int $calls = 0;
            public function __construct(private InMemoryTransactionManager $transactions, private DateTimeImmutable $now) {}
            public function now(): DateTimeImmutable {
                TestCase::assertSame('r-1', $this->transactions->lockedResearcher);
                $this->calls++;
                return $this->now;
            }
        };
        $this->transactions->afterLock = function (): void {
            $this->seedLoan(2, '2026-11-17T00:00:00Z');
        };
        $this->rejectsUnchanged('RESEARCHER_LIMIT_EXCEEDED', fn () => $this->serviceWithClock($clock)->execute($this->actor, 'R-1', ['c']));
        self::assertSame(1, $clock->calls);
    }

    public function test_equipment_writes_use_stable_id_order_independent_of_request_order(): void
    {
        $written = [];
        $this->equipment->beforeSave = function (InMemoryEquipmentRepository $repository) use (&$written): void {
            $written[] = [$repository->rows['a']->version, $repository->rows['b']->version];
            $repository->beforeSave = function (InMemoryEquipmentRepository $repository) use (&$written): void {
                $written[] = [$repository->rows['a']->version, $repository->rows['b']->version];
            };
        };
        $this->service->execute($this->actor, 'r-1', ['b', 'a']);
        self::assertSame([['1', '1'], ['2', '1']], $written);
    }

    public function test_second_write_conflict_rolls_back_first_write_and_preserves_existing_loans(): void
    {
        $this->seedLoan(1, '2026-11-17T00:00:00Z', 'r-2');
        $this->equipment->beforeSave = function (InMemoryEquipmentRepository $repository): void {
            $repository->beforeSave = static function (InMemoryEquipmentRepository $repository): void {
                TestCase::assertSame(1, $repository->rows['a']->equipment->availableUnits);
                throw new DomainViolation('EQUIPMENT_NOT_AVAILABLE', 'El equipo cambió durante la operación.');
            };
        };
        $this->rejectsUnchanged('EQUIPMENT_NOT_AVAILABLE', fn () => $this->service->execute($this->actor, 'r-1', ['b', 'a']));
        self::assertSame(['begin:r-1', 'rollback'], $this->transactions->events);
        $this->service->execute($this->actor, 'r-1', ['a']);
        self::assertCount(2, $this->loans->rows);
    }

    public static function storageFailures(): array { return [[new \RuntimeException('Fixture storage failure')], [new \Error('Fixture unexpected error')]]; }

    #[DataProvider('storageFailures')]
    public function test_failure_after_loan_insert_rolls_back_all_writes_and_propagates_error(\Throwable $failure): void
    {
        $this->seedLoan(1, '2026-11-17T00:00:00Z', 'r-2');
        $equipment = $this->equipment->rows;
        $loans = $this->loans->rows;
        $this->loans->afterAdd = function () use ($failure): void {
            self::assertCount(2, $this->loans->rows);
            self::assertSame(1, $this->equipment->rows['a']->equipment->availableUnits);
            self::assertSame(2, $this->equipment->rows['b']->equipment->availableUnits);
            throw $failure;
        };
        try {
            $this->service->execute($this->actor, 'r-1', ['a', 'b']);
            self::fail('Se ocultó el fallo de almacenamiento.');
        } catch (\Throwable $error) {
            self::assertSame($failure, $error);
        }
        self::assertSame($equipment, $this->equipment->rows);
        self::assertSame($loans, $this->loans->rows);
        self::assertNull($this->transactions->lockedResearcher);
        self::assertSame(['begin:r-1', 'rollback'], $this->transactions->events);
    }

    private function rejectsUnchanged(string $code, callable $operation): DomainViolation
    {
        $equipment = $this->equipment->rows;
        $loans = $this->loans->rows;
        try {
            $operation();
            self::fail('Se aceptó un préstamo inválido.');
        } catch (DomainViolation $error) {
            self::assertSame($code, $error->errorCode);
            self::assertSame($equipment, $this->equipment->rows);
            self::assertSame($loans, $this->loans->rows);
            self::assertNull($this->transactions->lockedResearcher);
            return $error;
        }
    }
}
