<?php

declare(strict_types=1);

namespace Tests\Application;

use DateTimeImmutable;
use Lab\Application\Ports\Clock;
use Lab\Application\Ports\IdGenerator;
use Lab\Application\Services\LendEquipmentService;
use Lab\Application\Services\ReturnItemService;
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

final class ReturnItemServiceTest extends TestCase
{
    private InMemoryEquipmentRepository $equipment;
    private InMemoryLoanRepository $loans;
    private InMemoryTransactionManager $transactions;
    private ReturnItemService $service;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->equipment = new InMemoryEquipmentRepository();
        $this->loans = new InMemoryLoanRepository();
        $items = [];
        $at = new DateTimeImmutable('2026-11-17T00:00:00Z');
        foreach (['a', 'b'] as $id) {
            $equipment = Equipment::create($id, 'Equipo ' . $id, 'Fabricante', new Reference('LAB-' . $id),
                'category-1', 'medición', new Quantity(2));
            $items[] = LoanItem::borrow('item-' . $id, $equipment, $at);
            $this->equipment->add($equipment->withdraw());
        }
        $this->loans->add(new Loan('loan-1', 'researcher-1', 'operator-1', $at, $items));
        $this->transactions = new InMemoryTransactionManager($this->equipment, $this->loans);
        $this->now = new DateTimeImmutable('2026-11-17T19:00:00-05:00');
        $this->service = $this->serviceAt(new FrozenClock($this->now));
    }

    private function serviceAt(Clock $clock): ReturnItemService
    {
        return new ReturnItemService($this->loans, $this->equipment, $this->transactions, $clock);
    }

    public function test_return_updates_only_target_line_and_restocks_once_in_utc(): void
    {
        $before = $this->loans->findById('loan-1');
        $result = $this->service->execute('LOAN-1', 'ITEM-A')->jsonSerialize();
        self::assertSame(['id' => 'item-a', 'equipment_id' => 'a', 'equipment_name' => 'Equipo a',
            'reference' => 'LAB-A', 'category_name' => 'medición', 'due_at' => '2026-11-22T00:00:00Z',
            'returned_at' => '2026-11-18T00:00:00Z'], $result);
        $after = $this->loans->findById('loan-1');
        self::assertSame($before->id, $after->id);
        self::assertSame($before->researcherId, $after->researcherId);
        self::assertSame($before->loanedByUserId, $after->loanedByUserId);
        self::assertEquals($before->loanedAt, $after->loanedAt);
        self::assertSame($before->items[1], $after->items[1]);
        self::assertSame('open', $after->status($this->now));
        self::assertSame(2, $this->equipment->findActive('a')->equipment->availableUnits);
        self::assertSame(1, $this->equipment->findActive('b')->equipment->availableUnits);
        self::assertCount(1, $this->loans->rows);
        self::assertSame(['begin:researcher-1', 'commit'], $this->transactions->events);
        self::assertNull($this->transactions->lockedResearcher);
    }

    public function test_second_return_is_rejected_without_another_stock_increment_or_timestamp_change(): void
    {
        $this->service->execute('loan-1', 'item-a');
        $this->equipment->lookups = [];
        $this->rejects('ITEM_ALREADY_RETURNED', fn () => $this->serviceAt(new FrozenClock($this->now->modify('+1 day')))->execute('loan-1', 'item-a'));
        self::assertSame([], $this->equipment->lookups);
    }

    public function test_returning_all_lines_closes_loan_without_deleting_history(): void
    {
        $this->service->execute('loan-1', 'item-a');
        $this->service->execute('loan-1', 'item-b');
        $loan = $this->loans->findById('loan-1');
        self::assertSame('closed', $loan->status($this->now));
        self::assertCount(2, $loan->items);
        self::assertSame([], $this->loans->pendingItemsForResearcher('researcher-1'));
        self::assertSame(2, $this->equipment->findActive('b')->equipment->availableUnits);
    }

    public function test_missing_loan_does_not_start_transaction(): void
    {
        $this->rejects('NOT_FOUND', fn () => $this->service->execute('missing', 'item-a'));
        self::assertSame([], $this->transactions->events);
    }

    public function test_missing_or_foreign_line_does_not_touch_stock(): void
    {
        $a = $this->equipment->rows['a']->equipment;
        $at = new DateTimeImmutable('2026-11-17T00:00:00Z');
        $this->loans->add(new Loan('loan-2', 'researcher-2', 'operator-2', $at, [LoanItem::borrow('foreign-item', $a, $at)]));
        foreach (['missing', 'foreign-item'] as $id) {
            $this->rejects('NOT_FOUND', fn () => $this->service->execute('loan-1', $id));
        }
        self::assertSame([], $this->equipment->lookups);
    }

    public function test_catalog_changes_do_not_rewrite_frozen_line_and_current_stock_is_used(): void
    {
        $a = $this->equipment->findActive('a');
        $this->equipment->save($a->equipment->revise('Nuevo nombre', 'Nuevo fabricante', 'category-2', 'óptica', new Quantity(4)), $a->version);
        $result = $this->service->execute('loan-1', 'item-a')->jsonSerialize();
        self::assertSame('Equipo a', $result['equipment_name']);
        self::assertSame('medición', $result['category_name']);
        self::assertSame('LAB-A', $result['reference']);
        self::assertSame(4, $this->equipment->findActive('a')->equipment->availableUnits);
        self::assertSame('Nuevo nombre', $this->equipment->findActive('a')->equipment->name);
    }

    public function test_inconsistent_full_stock_prevents_recording_return(): void
    {
        $a = $this->equipment->findActive('a');
        $this->equipment->save($a->equipment->restock(), $a->version);
        $this->rejects('VALIDATION_ERROR', fn () => $this->service->execute('loan-1', 'item-a'));
    }

    public static function unavailableEquipment(): array { return [['missing'], ['retired']]; }

    #[DataProvider('unavailableEquipment')]
    public function test_missing_or_inconsistently_retired_equipment_does_not_record_return(string $state): void
    {
        if ($state === 'missing') {
            unset($this->equipment->rows['a']);
        } else {
            $a = $this->equipment->findActive('a');
            $this->equipment->save($a->equipment->restock()->retire($this->now), $a->version);
        }
        $this->rejects('NOT_FOUND', fn () => $this->service->execute('loan-1', 'item-a'));
    }

    public function test_time_before_loan_is_rejected_without_writes(): void
    {
        $this->rejects('VALIDATION_ERROR', fn () => $this->serviceAt(new FrozenClock(new DateTimeImmutable('2026-11-16T23:59:59Z')))->execute('loan-1', 'item-a'));
    }

    public function test_clock_is_read_once_after_acquiring_same_researcher_lock_as_lending(): void
    {
        $clock = new class($this->transactions, $this->now) implements Clock {
            public int $calls = 0;
            public function __construct(private InMemoryTransactionManager $transactions, private DateTimeImmutable $now) {}
            public function now(): DateTimeImmutable {
                TestCase::assertSame('researcher-1', $this->transactions->lockedResearcher);
                $this->calls++;
                return $this->now;
            }
        };
        $this->serviceAt($clock)->execute('loan-1', 'item-a');
        self::assertSame(1, $clock->calls);
    }

    public function test_loan_is_reloaded_after_lock_instead_of_using_initial_state(): void
    {
        $this->transactions->afterLock = function (): void {
            $this->loans->markReturned('loan-1', 'item-a', $this->now);
        };
        $this->rejects('ITEM_ALREADY_RETURNED', fn () => $this->service->execute('loan-1', 'item-a'));
        self::assertSame([], $this->equipment->lookups);
    }

    public function test_conditional_return_conflict_does_not_restock(): void
    {
        $this->loans->beforeReturn = static function (): void {
            throw new DomainViolation('ITEM_ALREADY_RETURNED', 'Esta unidad ya fue devuelta.');
        };
        $this->rejects('ITEM_ALREADY_RETURNED', fn () => $this->service->execute('loan-1', 'item-a'));
    }

    public function test_stock_version_conflict_rolls_back_return_timestamp_and_allows_later_retry(): void
    {
        $this->equipment->beforeSave = function (): void {
            self::assertNotNull($this->loans->findById('loan-1')->items[0]->returnedAt);
            throw new DomainViolation('EQUIPMENT_NOT_AVAILABLE', 'El equipo cambió durante la operación.');
        };
        $this->rejects('EQUIPMENT_NOT_AVAILABLE', fn () => $this->service->execute('loan-1', 'item-a'));
        self::assertSame(['begin:researcher-1', 'rollback'], $this->transactions->events);
        $this->service->execute('loan-1', 'item-a');
        self::assertSame(2, $this->equipment->findActive('a')->equipment->availableUnits);
    }

    public static function storageFailures(): array { return [[new \RuntimeException('Fixture failure')], [new \Error('Fixture failure')]]; }

    #[DataProvider('storageFailures')]
    public function test_failure_after_recording_return_rolls_back_and_propagates_same_error(\Throwable $failure): void
    {
        $beforeLoans = $this->loans->rows;
        $beforeEquipment = $this->equipment->rows;
        $this->loans->afterReturn = function () use ($failure): void {
            self::assertNotNull($this->loans->findById('loan-1')->items[0]->returnedAt);
            throw $failure;
        };
        try {
            $this->service->execute('loan-1', 'item-a');
            self::fail('Se ocultó el error de escritura.');
        } catch (\Throwable $error) {
            self::assertSame($failure, $error);
        }
        self::assertSame($beforeLoans, $this->loans->rows);
        self::assertSame($beforeEquipment, $this->equipment->rows);
        self::assertNull($this->transactions->lockedResearcher);
    }

    public function test_returning_all_overdue_lines_restores_eligibility_for_lending(): void
    {
        $clock = new FrozenClock(new DateTimeImmutable('2026-11-24T00:00:00Z'));
        $return = $this->serviceAt($clock);
        $researchers = new InMemoryResearcherReader([new Researcher('researcher-1', 'Investigador', new DocumentNumber('001'))]);
        $ids = new class implements IdGenerator {
            private int $next = 0;
            public function generate(): string { return 'new-' . ++$this->next; }
        };
        $lend = new LendEquipmentService($this->equipment, $researchers, $this->loans, $this->transactions, $clock, $ids);
        $actor = new User('operator-2', 'operator', 'fixture-hash', 'lab_technician');
        $return->execute('loan-1', 'item-a');
        self::assertSame('overdue', $this->loans->findById('loan-1')->status($clock->now()));
        $this->rejects('RESEARCHER_HAS_OVERDUE', fn () => $lend->execute($actor, 'researcher-1', ['a']));
        $return->execute('loan-1', 'item-b');
        $result = $lend->execute($actor, 'researcher-1', ['a', 'b'])->jsonSerialize();
        self::assertSame('closed', $this->loans->findById('loan-1')->status($clock->now()));
        self::assertSame('open', $result['status']);
        self::assertCount(2, $this->loans->rows);
        self::assertCount(2, $this->loans->pendingItemsForResearcher('researcher-1'));
    }

    private function rejects(string $code, callable $operation): void
    {
        $beforeLoans = $this->loans->rows;
        $beforeEquipment = $this->equipment->rows;
        try {
            $operation();
            self::fail('Se aceptó una operación inválida.');
        } catch (DomainViolation $error) {
            self::assertSame($code, $error->errorCode);
        }
        self::assertSame($beforeLoans, $this->loans->rows);
        self::assertSame($beforeEquipment, $this->equipment->rows);
        self::assertNull($this->transactions->lockedResearcher);
    }
}
