<?php

declare(strict_types=1);

namespace Lab\Adapters\Security;

use Lab\Application\Ports\PasswordHasher;
use SensitiveParameter;

final readonly class ArgonPasswordHasher implements PasswordHasher
{
    private string $dummyHash;

    public function __construct()
    {
        $this->dummyHash = $this->hash(bin2hex(random_bytes(32)));
    }

    public function hash(#[SensitiveParameter] string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID, ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 1]);
    }

    public function verify(#[SensitiveParameter] string $password, #[SensitiveParameter] ?string $hash): bool
    {
        $recognized = $hash !== null && password_get_info($hash)['algoName'] === 'argon2id';
        $candidate = $recognized ? $hash : $this->dummyHash;
        $verified = password_verify($password, $candidate);

        return $recognized && $verified;
    }

    public function __debugInfo(): array { return ['algorithm' => 'argon2id']; }
}
