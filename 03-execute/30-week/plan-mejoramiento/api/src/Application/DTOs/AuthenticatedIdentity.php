<?php

declare(strict_types=1);

namespace Lab\Application\DTOs;

use Lab\Domain\UserRole;

final readonly class AuthenticatedIdentity
{
    public function __construct(public string $userId, public UserRole $role) {}
}
