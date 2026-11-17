<?php

declare(strict_types=1);

namespace Lab\Application\Ports;

use SensitiveParameter;

interface PasswordHasher
{
    public function hash(#[SensitiveParameter] string $password): string;

    /** A missing hash must still perform a dummy verification and return false. */
    public function verify(#[SensitiveParameter] string $password, #[SensitiveParameter] ?string $hash): bool;
}
