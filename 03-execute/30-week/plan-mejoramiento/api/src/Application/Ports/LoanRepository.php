<?php

declare(strict_types=1);

namespace Lab\Application\Ports;

use DateTimeImmutable;
use Lab\Domain\Loan;
use Lab\Domain\LoanItem;

interface LoanRepository
{
    public function findById(string $id): ?Loan;

    /** @return list<LoanItem> All unreturned lines, including overdue ones, for this researcher only. */
    public function pendingItemsForResearcher(string $researcherId): array;

    /** Insert the header and all frozen lines in the caller's transaction; never replace a loan. */
    public function add(Loan $loan): void;

    /**
     * Change only returned_at in the caller's transaction, requiring the matching loan and an unreturned line.
     * A missing loan/line is NOT_FOUND; an already returned line is ITEM_ALREADY_RETURNED.
     * Never replace frozen fields or other lines. All loan ownership is immutable.
     */
    public function markReturned(string $loanId, string $itemId, DateTimeImmutable $at): void;
}
