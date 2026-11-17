<?php

declare(strict_types=1);

namespace Tests\Integration;

use DateTimeImmutable;
use Firebase\JWT\JWT;
use InvalidArgumentException;
use Lab\Adapters\Security\JwtTokens;
use Lab\Domain\DomainViolation;
use Lab\Domain\User;
use Lab\Domain\UserRole;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\FrozenClock;

final class JwtTokensTest extends TestCase
{
    private string $secret;
    private DateTimeImmutable $now;
    private JwtTokens $tokens;

    protected function setUp(): void
    {
        $this->secret = bin2hex(random_bytes(32));
        $this->now = new DateTimeImmutable('2026-11-16T23:00:00Z');
        $this->tokens = new JwtTokens($this->secret, new FrozenClock($this->now));
    }

    private function user(string $role = 'admin'): User
    {
        return new User('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'operator', 'fixture-hash', $role);
    }

    private function claims(): array
    {
        return ['sub' => $this->user()->id, 'role' => 'admin', 'iat' => $this->now->getTimestamp(), 'exp' => $this->now->getTimestamp() + 28800];
    }

    #[DataProvider('roles')]
    public function test_signed_token_contains_only_identity_and_eight_hour_lifetime(string $role): void
    {
        $token = $this->tokens->issue($this->user($role), 28800);
        $identity = $this->tokens->verify($token);
        self::assertSame($this->user()->id, $identity->userId);
        self::assertSame(UserRole::from($role), $identity->role);
        $payload = json_decode(JWT::urlsafeB64Decode(explode('.', $token)[1]), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(array_replace($this->claims(), ['role' => $role]), $payload);
        self::assertStringNotContainsString('fixture-hash', json_encode($payload));
    }

    public static function roles(): array { return [['admin'], ['lab_technician']]; }

    public function test_expiration_is_strict_at_eight_hours(): void
    {
        $token = $this->tokens->issue($this->user(), 28800);
        $before = new JwtTokens($this->secret, new FrozenClock($this->now->modify('+28799 seconds')));
        self::assertSame($this->user()->id, $before->verify($token)->userId);
        $expired = new JwtTokens($this->secret, new FrozenClock($this->now->modify('+28800 seconds')));
        $this->assertUnauthenticated(fn () => $expired->verify($token));
    }

    public function test_wrong_signature_and_modified_role_are_rejected(): void
    {
        $wrongKey = JWT::encode($this->claims(), bin2hex(random_bytes(32)), 'HS256');
        $this->assertUnauthenticated(fn () => $this->tokens->verify($wrongKey));
        $parts = explode('.', $this->tokens->issue($this->user('lab_technician'), 28800));
        $parts[1] = JWT::urlsafeB64Encode(json_encode($this->claims(), JSON_THROW_ON_ERROR));
        $this->assertUnauthenticated(fn () => $this->tokens->verify(implode('.', $parts)));
    }

    public function test_none_and_other_algorithms_are_rejected(): void
    {
        $unsigned = JWT::urlsafeB64Encode('{"alg":"none","typ":"JWT"}') . '.'
            . JWT::urlsafeB64Encode(json_encode($this->claims(), JSON_THROW_ON_ERROR)) . '.';
        $this->assertUnauthenticated(fn () => $this->tokens->verify($unsigned));
        $otherAlgorithm = JWT::encode($this->claims(), $this->secret, 'HS512');
        $this->assertUnauthenticated(fn () => $this->tokens->verify($otherAlgorithm));
    }

    #[DataProvider('invalidClaims')]
    public function test_signed_but_invalid_claims_are_rejected(array $changes): void
    {
        $token = JWT::encode(array_replace($this->claims(), $changes), $this->secret, 'HS256');
        $this->assertUnauthenticated(fn () => $this->tokens->verify($token));
    }

    public static function invalidClaims(): array
    {
        $now = (new DateTimeImmutable('2026-11-16T23:00:00Z'))->getTimestamp();
        return [
            [['role' => 'researcher']], [['role' => null]], [['sub' => 'not-a-uuid']], [['sub' => null]],
            [['iat' => (string) $now]], [['iat' => null]], [['exp' => null]], [['exp' => $now + 28801]],
            [['iat' => $now + 1, 'exp' => $now + 28801, 'nbf' => 0]],
            [['nbf' => $now + 1]],
        ];
    }

    #[DataProvider('malformedTokens')]
    public function test_malformed_tokens_fail_without_exposing_details(string $token): void
    {
        $this->assertUnauthenticated(fn () => $this->tokens->verify($token));
    }

    public static function malformedTokens(): array { return [[''], ['not-a-token'], ['a.b.c'], [str_repeat('x', 8193)]]; }

    public function test_library_clock_and_leeway_are_restored_after_success_and_failure(): void
    {
        $previousTimestamp = JWT::$timestamp;
        $previousLeeway = JWT::$leeway;
        try {
            JWT::$timestamp = 123;
            JWT::$leeway = 60;
            $this->tokens->verify($this->tokens->issue($this->user(), 28800));
            self::assertSame(123, JWT::$timestamp);
            self::assertSame(60, JWT::$leeway);
            $this->assertUnauthenticated(fn () => $this->tokens->verify('a.b.c'));
            self::assertSame(123, JWT::$timestamp);
            self::assertSame(60, JWT::$leeway);
        } finally {
            JWT::$timestamp = $previousTimestamp;
            JWT::$leeway = $previousLeeway;
        }
    }

    public function test_short_secret_is_rejected_without_echoing_it(): void
    {
        try {
            new JwtTokens('short-test-secret', new FrozenClock($this->now));
            self::fail('Se aceptó un secreto demasiado corto.');
        } catch (InvalidArgumentException $error) {
            self::assertStringNotContainsString('short-test-secret', $error->getMessage());
        }
    }

    public function test_issuer_does_not_allow_arbitrary_lifetime(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->tokens->issue($this->user(), 86400);
    }

    public function test_debug_representation_hides_secret(): void
    {
        ob_start();
        var_dump($this->tokens);
        $output = ob_get_clean();
        self::assertStringNotContainsString($this->secret, $output);
    }

    private function assertUnauthenticated(callable $operation): void
    {
        try {
            $operation();
            self::fail('Se aceptó un token inválido.');
        } catch (DomainViolation $error) {
            self::assertSame('UNAUTHENTICATED', $error->errorCode);
            self::assertSame('La sesión no es válida o ha vencido.', $error->getMessage());
            self::assertNull($error->getPrevious());
        }
    }
}
