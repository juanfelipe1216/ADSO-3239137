<?php

declare(strict_types=1);

namespace Tests\Fakes;

use Closure;
use Lab\Application\Ports\LoanRepository;
use Lab\Domain\Loan;

final class InMemoryLoanRepository implements LoanRepository
{
    /** @var array<string, Loan> */
    public array $rows = [];
    public ?Closure $afterAdd = null;

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
