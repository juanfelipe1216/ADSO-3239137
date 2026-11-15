<?php

declare(strict_types=1);

namespace Lab\Domain;

use JsonSerializable;
use Lab\Domain\ValueObjects\RequiredText;
use SensitiveParameter;

final readonly class User implements JsonSerializable
{
    public string $username;
    public UserRole $role;
    private string $passwordHash;

    public function __construct(
        public string $id,
        string $username,
        #[SensitiveParameter] string $passwordHash,
        string $role,
    ) {
        $this->username = self::normalizeUsername($username);
        $this->role = UserRole::parse($role);
        if (trim($passwordHash) === '' || strlen($passwordHash) > 512) {
            throw new DomainViolation('VALIDATION_ERROR', 'El hash de contraseña no es válido.');
        }
        $this->passwordHash = $passwordHash;
    }

    public static function normalizeUsername(string $username): string
    {
        return RequiredText::normalize(mb_strtolower(trim($username), 'UTF-8'), 120);
    }

    public function passwordHash(): string
    {
        return $this->passwordHash;
    }

    public function jsonSerialize(): array
    {
        return ['id' => $this->id, 'username' => $this->username, 'role' => $this->role->value];
    }

    public function __debugInfo(): array
    {
        return $this->jsonSerialize();
    }
}
