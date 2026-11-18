<?php

declare(strict_types=1);

namespace Tests\Fakes;

use Closure;
use Lab\Application\Ports\TransactionManager;
use Throwable;

/** Single-process rollback fake; it does not demonstrate concurrent database locking. */
final class InMemoryTransactionManager implements TransactionManager
{
    public ?string $lockedResearcher = null;
    public array $events = [];
    public ?Closure $afterLock = null;

    public function __construct(private InMemoryEquipmentRepository $equipment, private InMemoryLoanRepository $loans) {}

    public function forResearcher(string $researcherId, callable $operation): mixed
    {
        if ($this->lockedResearcher !== null) {
            throw new \LogicException('Nested transactions are not supported by this fake.');
        }
        $equipment = $this->equipment->rows;
        $loans = $this->loans->rows;
        $this->lockedResearcher = $researcherId;
        $this->events[] = 'begin:' . $researcherId;
        try {
            if ($this->afterLock !== null) {
                ($this->afterLock)();
            }
            $result = $operation();
            $this->events[] = 'commit';
            return $result;
        } catch (Throwable $error) {
            $this->equipment->rows = $equipment;
            $this->loans->rows = $loans;
            $this->events[] = 'rollback';
            throw $error;
        } finally {
            $this->lockedResearcher = null;
        }
    }
}
