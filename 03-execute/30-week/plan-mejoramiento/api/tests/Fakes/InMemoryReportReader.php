<?php

declare(strict_types=1);

namespace Tests\Fakes;

use Lab\Application\DTOs\MostBorrowedRow;
use Lab\Application\Ports\ReportReader;
use Lab\Domain\ValueObjects\DateRange;

final class InMemoryReportReader implements ReportReader
{
    public int $calls = 0;

    public function __construct(private InMemoryLoanRepository $loans) {}

    public function mostBorrowed(DateRange $range): array
    {
        $this->calls++;
        $groups = [];
        foreach ($this->loans->rows as $loan) {
            if ($loan->loanedAt < $range->from || $loan->loanedAt >= $range->exclusiveEnd()) {
                continue;
            }
            foreach ($loan->items as $item) {
                $key = json_encode([$item->equipmentId, $item->equipmentName, $item->categoryName], JSON_THROW_ON_ERROR);
                $groups[$key] = new MostBorrowedRow($item->equipmentId, $item->equipmentName, $item->categoryName,
                    ($groups[$key]->timesBorrowed ?? 0) + 1);
            }
        }
        $rows = array_values($groups);
        usort($rows, static fn (MostBorrowedRow $left, MostBorrowedRow $right): int =>
            ($right->timesBorrowed <=> $left->timesBorrowed)
            ?: strcmp($left->equipmentName, $right->equipmentName)
            ?: strcmp($left->categoryName, $right->categoryName)
            ?: strcmp($left->equipmentId, $right->equipmentId));
        return $rows;
    }
}
