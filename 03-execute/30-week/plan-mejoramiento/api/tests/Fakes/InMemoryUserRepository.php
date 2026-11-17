<?php

declare(strict_types=1);

namespace Tests\Fakes;

use Lab\Application\Ports\UserRepository;
use Lab\Domain\DomainViolation;
use Lab\Domain\User;

final class InMemoryUserRepository implements UserRepository
{
    /** @var array<string, User> */
    public array $users = [];
    public array $lookups = [];

    public function findByUsername(string $username): ?User
    {
        $this->lookups[] = $username;
        return $this->users[$username] ?? null;
    }

    public function add(User $user): void
    {
        if (isset($this->users[$user->username])) {
            throw new DomainViolation('USERNAME_TAKEN', 'El nombre de usuario ya está registrado.');
        }
        $this->users[$user->username] = $user;
    }
}
