<?php

declare(strict_types=1);

namespace Lab\Domain\ValueObjects;

use DateTimeImmutable;
use DateTimeZone;
use Lab\Domain\DomainViolation;

final readonly class DateRange
{
    public DateTimeImmutable $from;
    public DateTimeImmutable $to;

    public function __construct(string $from, string $to)
    {
        $this->from = self::parse($from);
        $this->to = self::parse($to);
        if ($this->to < $this->from) {
            throw new DomainViolation('INVALID_DATE_RANGE', 'La fecha final no puede ser anterior a la inicial.');
        }
    }

    public function exclusiveEnd(): DateTimeImmutable
    {
        return $this->to->modify('+1 day');
    }

    private static function parse(string $date): DateTimeImmutable
    {
        if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D', $date) !== 1) {
            throw new DomainViolation('VALIDATION_ERROR', 'La fecha debe tener formato AAAA-MM-DD.');
        }
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date, new DateTimeZone('UTC'));
        if ($parsed === false || $parsed->format('Y-m-d') !== $date) {
            throw new DomainViolation('VALIDATION_ERROR', 'La fecha debe ser válida y tener formato AAAA-MM-DD.');
        }
        return $parsed;
    }
}
