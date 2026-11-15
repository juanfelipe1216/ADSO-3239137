<?php

declare(strict_types=1);

namespace Lab\Domain\ValueObjects;

use Lab\Domain\DomainViolation;

final readonly class Quantity
{
    public function __construct(public int $value)
    {
        if ($value < 1) {
            throw new DomainViolation('VALIDATION_ERROR', 'La cantidad debe ser mayor que cero.');
        }
    }
}
