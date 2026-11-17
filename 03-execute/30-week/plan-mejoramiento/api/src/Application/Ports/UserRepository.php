<?php

declare(strict_types=1);

namespace Lab\Application\Ports;

use Lab\Domain\DomainViolation;
use Lab\Domain\User;

interface UserRepository
{
    public function findByUsername(string $username): ?User;

    /** @throws DomainViolation With USERNAME_TAKEN if the normalized username already exists, including concurrent inserts. */
    public function add(User $user): void;
}
