<?php

declare(strict_types=1);

namespace Lab\Application\Ports;

use Lab\Application\DTOs\AuthenticatedIdentity;
use SensitiveParameter;

interface TokenVerifier
{
    public function verify(#[SensitiveParameter] string $token): AuthenticatedIdentity;
}
