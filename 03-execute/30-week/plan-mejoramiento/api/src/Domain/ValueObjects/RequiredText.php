<?php

declare(strict_types=1);

namespace Lab\Domain\ValueObjects;

use Lab\Domain\DomainViolation;

final class RequiredText
{
    public static function normalize(string $value, int $maximum): string
    {
        $value = trim($value);
        if ($value === '' || mb_strlen($value, 'UTF-8') > $maximum) {
            throw new DomainViolation('VALIDATION_ERROR', "El texto debe tener entre 1 y {$maximum} caracteres.");
        }

        return $value;
    }
}
