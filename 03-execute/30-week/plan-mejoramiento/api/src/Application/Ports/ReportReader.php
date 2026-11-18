<?php

declare(strict_types=1);

namespace Lab\Application\Ports;

use Lab\Application\DTOs\MostBorrowedRow;
use Lab\Domain\ValueObjects\DateRange;

interface ReportReader
{
    /**
     * Count all lines, including returned ones, by equipment id and frozen name/category.
     * Filter loaned_at >= from and < exclusiveEnd in UTC; never read labels from the live catalog.
     * Sort by count descending, then name/category/id ascending (binary text order).
     * @return list<MostBorrowedRow>
     */
    public function mostBorrowed(DateRange $range): array;
}
