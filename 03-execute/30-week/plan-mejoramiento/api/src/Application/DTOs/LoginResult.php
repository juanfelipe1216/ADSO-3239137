<?php

declare(strict_types=1);

namespace Lab\Application\DTOs;

use JsonSerializable;
use Lab\Domain\UserRole;

final readonly class LoginResult implements JsonSerializable
{
    public function __construct(
        public string $accessToken,
        public int $expiresIn,
        public UserRole $role,
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'access_token' => $this->accessToken,
            'token_type' => 'Bearer',
            'expires_in' => $this->expiresIn,
            'role' => $this->role->value,
        ];
    }
}
