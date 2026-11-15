<?php

declare(strict_types=1);

namespace Lab\Domain\ValueObjects;

use Lab\Domain\DomainViolation;

final readonly class DocumentNumber
{
    public string $value;

    public function __construct(string $value)
    {
        $normalized = trim($value);
        if ($normalized === '' || mb_strlen($normalized, 'UTF-8') > 30) {
            throw new DomainViolation('VALIDATION_ERROR', 'El documento debe tener entre 1 y 30 caracteres.');
        }
        $this->value = $normalized;
    }
}
