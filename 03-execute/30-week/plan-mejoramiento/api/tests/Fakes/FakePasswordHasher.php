<?php

declare(strict_types=1);

namespace Tests\Fakes;

use Lab\Application\Ports\PasswordHasher;
use SensitiveParameter;

final class FakePasswordHasher implements PasswordHasher
{
    public array $checks = [];
    public array $hashedPasswords = [];

    /** @param array<string, string> $knownHashes Test fixtures only; this fake performs no cryptography. */
    public function __construct(private array $knownHashes = []) {}

    public function hash(#[SensitiveParameter] string $password): string
    {
        $this->hashedPasswords[] = $password;
        $key = 'fixture-generated-' . count($this->hashedPasswords);
        $this->knownHashes[$key] = $password;
        return $key;
    }

    public function verify(#[SensitiveParameter] string $password, #[SensitiveParameter] ?string $hash): bool
    {
        $this->checks[] = [$password, $hash];
        return $hash !== null && isset($this->knownHashes[$hash]) && $this->knownHashes[$hash] === $password;
    }
}
