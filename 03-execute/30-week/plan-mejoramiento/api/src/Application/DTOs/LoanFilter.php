<?php

declare(strict_types=1);

namespace Lab\Application\DTOs;

use DateTimeImmutable;
use Lab\Domain\DomainViolation;
use Lab\Domain\ValueObjects\DateRange;

final readonly class LoanFilter
{
    public ?DateTimeImmutable $from;
    public ?DateTimeImmutable $until;

    public function __construct(?string $from, ?string $to, public ?string $status, public ?string $researcherId)
    {
        $this->from = $from === null ? null : (new DateRange($from, $from))->from;
        $this->until = $to === null ? null : (new DateRange($to, $to))->exclusiveEnd();
        if ($from !== null && $to !== null) {
            new DateRange($from, $to);
        }
        if ($status !== null && !in_array($status, ['open', 'overdue', 'closed'], true)) {
            throw new DomainViolation('VALIDATION_ERROR', 'El estado debe ser open, overdue o closed.');
        }
    }
}
