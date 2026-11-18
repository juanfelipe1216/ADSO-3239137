<?php

declare(strict_types=1);

namespace Lab\Domain;

final class DomainViolation extends \DomainException
{
    /** @param array<string, int|string|bool> $context Business facts, without HTTP types. */
    public function __construct(public readonly string $errorCode, string $message, public readonly array $context = [])
    {
        parent::__construct($message);
    }
}
