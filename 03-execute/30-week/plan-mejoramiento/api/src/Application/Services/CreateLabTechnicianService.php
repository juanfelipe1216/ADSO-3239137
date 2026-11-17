<?php

declare(strict_types=1);

namespace Lab\Application\Services;

use Lab\Application\Ports\IdGenerator;
use Lab\Application\Ports\PasswordHasher;
use Lab\Application\Ports\UserRepository;
use Lab\Domain\DomainViolation;
use Lab\Domain\User;
use Lab\Domain\UserRole;
use SensitiveParameter;

final readonly class CreateLabTechnicianService
{
    public function __construct(
        private UserRepository $users,
        private PasswordHasher $passwords,
        private IdGenerator $ids,
    ) {}

    public function execute(
        User $actor,
        string $username,
        #[SensitiveParameter] string $password,
        string $role,
    ): User {
        if ($actor->role !== UserRole::Admin) {
            throw new DomainViolation('FORBIDDEN_ROLE', 'Solo el administrador puede crear laboratoristas.');
        }
        if ($role !== UserRole::LabTechnician->value) {
            throw new DomainViolation('VALIDATION_ERROR', 'Solo se permite crear usuarios con rol de laboratorista.');
        }
        $username = User::normalizeUsername($username);
        $length = mb_strlen($password, 'UTF-8');
        if ($length < 12 || $length > 128) {
            throw new DomainViolation('VALIDATION_ERROR', 'La contraseña debe tener entre 12 y 128 caracteres.');
        }
        if ($this->users->findByUsername($username) !== null) {
            throw new DomainViolation('USERNAME_TAKEN', 'El nombre de usuario ya está registrado.');
        }

        $user = new User($this->ids->generate(), $username, $this->passwords->hash($password), $role);
        $this->users->add($user);

        return $user;
    }
}
