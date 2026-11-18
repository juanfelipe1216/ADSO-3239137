<?php

declare(strict_types=1);

namespace Lab\Application\Ports;

use Lab\Domain\Loan;
use Lab\Domain\LoanItem;

interface LoanRepository
{
    /** @return list<LoanItem> All unreturned lines, including overdue ones, for this researcher only. */
    public function pendingItemsForResearcher(string $researcherId): array;

    /** Insert the header and all frozen lines in the caller's transaction; never replace a loan. */
    public function add(Loan $loan): void;
}
