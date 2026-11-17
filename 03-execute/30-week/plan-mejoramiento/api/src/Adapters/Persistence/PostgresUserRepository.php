<?php

declare(strict_types=1);

namespace Lab\Adapters\Persistence;

use Illuminate\Database\ConnectionInterface;
use Lab\Application\Ports\UserRepository;
use Lab\Domain\DomainViolation;
use Lab\Domain\User;

final readonly class PostgresUserRepository implements UserRepository
{
    public function __construct(private ConnectionInterface $db) {}

    public function findByUsername(string $username): ?User
    {
        $row = $this->db->selectOne('SELECT id, username, password_hash, role FROM lab_equipment."user" WHERE username = ?', [$username]);
        return $row === null ? null : new User($row->id, $row->username, $row->password_hash, $row->role);
    }

    public function add(User $user): void
    {
        $row = $this->db->selectOne('INSERT INTO lab_equipment."user" (id, username, password_hash, role)
            VALUES (?, ?, ?, ?) ON CONFLICT (username) DO NOTHING RETURNING id',
            [$user->id, $user->username, $user->passwordHash(), $user->role->value]);
        if ($row === null) {
            throw new DomainViolation('USERNAME_TAKEN', 'El nombre de usuario ya está registrado.');
        }
    }
}
