<?php

declare(strict_types=1);

namespace Lab\Application\Services;

use Lab\Application\DTOs\MostBorrowedRow;
use Lab\Application\Ports\ReportReader;
use Lab\Domain\DomainViolation;
use Lab\Domain\User;
use Lab\Domain\UserRole;
use Lab\Domain\ValueObjects\DateRange;

final readonly class MostBorrowedReportService
{
    public function __construct(private ReportReader $reader) {}

    public function execute(User $actor, string $from, string $to): array
    {
        if ($actor->role !== UserRole::Admin) {
            throw new DomainViolation('FORBIDDEN_ROLE', 'Solo el administrador puede consultar este reporte.');
        }
        $range = new DateRange($from, $to);
        return ['from' => $range->from->format('Y-m-d'), 'to' => $range->to->format('Y-m-d'),
            'rows' => array_map(static fn (MostBorrowedRow $row): array => $row->jsonSerialize(), $this->reader->mostBorrowed($range))];
    }
}
