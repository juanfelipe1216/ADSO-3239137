<?php

declare(strict_types=1);

namespace Tests\Fakes;

use Closure;
use DateTimeImmutable;
use Lab\Application\Ports\LoanRepository;
use Lab\Domain\Loan;
use Lab\Domain\DomainViolation;

final class InMemoryLoanRepository implements LoanRepository
{
    /** @var array<string, Loan> */
    public array $rows = [];
    public ?Closure $afterAdd = null;
    public ?Closure $beforeReturn = null;
    public ?Closure $afterReturn = null;

    public function findById(string $id): ?Loan
    {
        return $this->rows[$id] ?? null;
    }

    public function markReturned(string $loanId, string $itemId, DateTimeImmutable $at): void
    {
        if ($this->beforeReturn !== null) {
            $hook = $this->beforeReturn;
            $this->beforeReturn = null;
            $hook($this);
        }
        $loan = $this->findById($loanId) ?? throw new DomainViolation('NOT_FOUND', 'El préstamo no existe.');
        $this->rows[$loanId] = $loan->returnItem($itemId, $at);
        if ($this->afterReturn !== null) {
            $hook = $this->afterReturn;
            $this->afterReturn = null;
            $hook($this);
        }
    }

    public function pendingItemsForResearcher(string $researcherId): array
    {
        $items = [];
        foreach ($this->rows as $loan) {
            if ($loan->researcherId === $researcherId) {
                foreach ($loan->items as $item) {
                    if ($item->returnedAt === null) {
                        $items[] = $item;
                    }
                }
            }
        }
        return $items;
    }

    public function add(Loan $loan): void
    {
        if (isset($this->rows[$loan->id])) {
            throw new \LogicException('Duplicate loan identifier in fixture.');
        }
        $this->rows[$loan->id] = $loan;
        if ($this->afterAdd !== null) {
            $hook = $this->afterAdd;
            $this->afterAdd = null;
            $hook($this);
        }
    }
}
