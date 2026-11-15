<?php

declare(strict_types=1);

namespace Lab\Application\Ports;

use Lab\Domain\User;

interface UserRepository
{
    public function findByUsername(string $username): ?User;
}
