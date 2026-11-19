<?php
declare(strict_types=1);
namespace Lab\Application\Services;

use Lab\Application\Ports\UserRepository;
use Lab\Application\Ports\PasswordHasher;
use Lab\Application\Ports\IdGenerator;
use Lab\Domain\User;
use Lab\Domain\UserRole;
use Lab\Domain\DomainViolation;
use SensitiveParameter;

final readonly class BootstrapAdminService
{
    public function __construct(private UserRepository $users, private PasswordHasher $passwords, private IdGenerator $ids) {}

    public function execute(string $username, #[SensitiveParameter] string $password): void
    {
        $username = User::normalizeUsername($username);
        if (mb_strlen($password, 'UTF-8') < 12 || mb_strlen($password, 'UTF-8') > 128) {
            throw new DomainViolation('VALIDATION_ERROR', 'Configura una contraseña de administrador de 12 a 128 caracteres.');
        }
        $existing = $this->users->findByUsername($username);
        if ($existing === null) {
            try {
                $this->users->add(new User($this->ids->generate(), $username, $this->passwords->hash($password), 'admin'));
                return;
            } catch (DomainViolation $error) {
                if ($error->errorCode !== 'USERNAME_TAKEN') {
                    throw $error;
                }
                $existing = $this->users->findByUsername($username);
            }
        }
        if ($existing === null || $existing->role !== UserRole::Admin
            || !$this->passwords->verify($password, $existing->passwordHash())) {
            throw new DomainViolation('VALIDATION_ERROR', 'La configuración del administrador no coincide con la cuenta existente.');
        }
    }
}
