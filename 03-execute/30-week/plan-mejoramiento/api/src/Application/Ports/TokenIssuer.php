<?php

declare(strict_types=1);

namespace Lab\Application\Ports;

use Lab\Domain\User;

interface TokenIssuer
{
    public function issue(User $user, int $lifetimeSeconds): string;
}
