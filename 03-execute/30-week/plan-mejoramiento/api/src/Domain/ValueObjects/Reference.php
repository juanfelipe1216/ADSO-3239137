<?php

declare(strict_types=1);

namespace Lab\Domain\ValueObjects;

use Lab\Domain\DomainViolation;

final readonly class Reference
{
    public string $value;

    public function __construct(string $value)
    {
        $normalized = strtoupper(trim($value));
        if (preg_match('~^[A-Z0-9][A-Z0-9._/\-]{0,63}$~D', $normalized) !== 1) {
            throw new DomainViolation('VALIDATION_ERROR', 'La referencia debe tener entre 1 y 64 caracteres alfanuméricos o separadores . _ / -.');
        }
        $this->value = $normalized;
    }
}
