<?php

declare(strict_types=1);

namespace Lab\Domain;

enum UserRole: string
{
    case Admin = 'admin';
    case LabTechnician = 'lab_technician';

    public static function parse(string $value): self
    {
        return self::tryFrom($value)
            ?? throw new DomainViolation('VALIDATION_ERROR', 'El rol indicado no es válido.');
    }
}
