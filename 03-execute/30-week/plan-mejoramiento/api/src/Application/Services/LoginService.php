<?php

declare(strict_types=1);

namespace Lab\Application\Services;

use Lab\Application\DTOs\LoginResult;
use Lab\Application\Ports\PasswordHasher;
use Lab\Application\Ports\TokenIssuer;
use Lab\Application\Ports\UserRepository;
use Lab\Domain\DomainViolation;
use Lab\Domain\User;
use SensitiveParameter;

final readonly class LoginService
{
    private const TOKEN_LIFETIME_SECONDS = 28800;

    public function __construct(
        private UserRepository $users,
        private PasswordHasher $passwords,
        private TokenIssuer $tokens,
    ) {}

    public function execute(string $username, #[SensitiveParameter] string $password): LoginResult
    {
        $username = User::normalizeUsername($username);
        $user = $this->users->findByUsername($username);
        $verified = $this->passwords->verify($password, $user?->passwordHash());
        if ($user === null || !$verified) {
            throw new DomainViolation('INVALID_CREDENTIALS', 'Usuario o contraseña incorrectos.');
        }

        return new LoginResult(
            $this->tokens->issue($user, self::TOKEN_LIFETIME_SECONDS),
            self::TOKEN_LIFETIME_SECONDS,
            $user->role,
        );
    }
}
