<?php

declare(strict_types=1);

namespace Tests\Integration;

use Lab\Application\Ports\Clock;
use Lab\Application\Ports\IdGenerator;
use Lab\Application\Ports\PasswordHasher;
use Lab\Application\Ports\TokenIssuer;
use Lab\Application\Ports\TokenVerifier;
use Lab\Application\Services\CreateLabTechnicianService;
use Lab\Application\Services\LoginService;
use Lab\Domain\DomainViolation;
use Lab\Domain\User;
use Tests\Fakes\InMemoryUserRepository;
use Tests\TestCase;

final class SecurityCompositionTest extends TestCase
{
    public function test_provider_composes_real_hash_and_tokens_for_create_and_login(): void
    {
        config(['lab.jwt_secret' => bin2hex(random_bytes(32))]);
        $hasher = $this->app->make(PasswordHasher::class);
        $issuer = $this->app->make(TokenIssuer::class);
        $verifier = $this->app->make(TokenVerifier::class);
        self::assertSame($issuer, $verifier);
        self::assertSame('UTC', $this->app->make(Clock::class)->now()->getTimezone()->getName());
        $users = new InMemoryUserRepository();
        $ids = new class implements IdGenerator {
            public function generate(): string { return 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb'; }
        };
        $admin = new User('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'admin', 'fixture-hash', 'admin');
        $password = bin2hex(random_bytes(20));
        $user = (new CreateLabTechnicianService($users, $hasher, $ids))->execute($admin, ' Operator ', $password, 'lab_technician');
        self::assertSame('argon2id', password_get_info($user->passwordHash())['algoName']);
        $login = new LoginService($users, $hasher, $issuer);
        $result = $login->execute(' OPERATOR ', $password);
        self::assertSame($user->id, $verifier->verify($result->accessToken)->userId);
        self::assertStringNotContainsString($password, json_encode($result));
        self::assertStringNotContainsString($user->passwordHash(), json_encode($result));
        $this->expectException(DomainViolation::class);
        $login->execute('operator', 'incorrecta');
    }

    public function test_missing_secret_does_not_fall_back_to_a_shared_default(): void
    {
        config(['lab.jwt_secret' => '']);
        $this->expectException(\InvalidArgumentException::class);
        $this->app->make(TokenIssuer::class);
    }
}
