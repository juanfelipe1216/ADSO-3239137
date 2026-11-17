<?php

declare(strict_types=1);

namespace Lab\Adapters\Security;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use InvalidArgumentException;
use Lab\Application\DTOs\AuthenticatedIdentity;
use Lab\Application\Ports\Clock;
use Lab\Application\Ports\TokenIssuer;
use Lab\Application\Ports\TokenVerifier;
use Lab\Domain\DomainViolation;
use Lab\Domain\User;
use Lab\Domain\UserRole;
use SensitiveParameter;

final readonly class JwtTokens implements TokenIssuer, TokenVerifier
{
    private const LIFETIME = 28800;
    private const UUID_PATTERN = '/\A[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/i';

    public function __construct(#[SensitiveParameter] private string $secret, private Clock $clock)
    {
        if (strlen($secret) < 32) {
            throw new InvalidArgumentException('JWT_SECRET debe contener al menos 32 bytes aleatorios.');
        }
    }

    public function issue(User $user, int $lifetimeSeconds): string
    {
        if ($lifetimeSeconds !== self::LIFETIME || !preg_match(self::UUID_PATTERN, $user->id)) {
            throw new InvalidArgumentException('El token requiere un usuario UUID v4 y una duración de ocho horas.');
        }
        $now = $this->clock->now()->getTimestamp();

        return JWT::encode(['sub' => $user->id, 'role' => $user->role->value, 'iat' => $now, 'exp' => $now + self::LIFETIME],
            $this->secret, 'HS256');
    }

    public function verify(#[SensitiveParameter] string $token): AuthenticatedIdentity
    {
        if ($token === '' || strlen($token) > 8192) {
            throw $this->unauthenticated();
        }
        $timestamp = JWT::$timestamp;
        $leeway = JWT::$leeway;
        $now = $this->clock->now()->getTimestamp();
        try {
            JWT::$timestamp = $now;
            JWT::$leeway = 0;
            $claims = JWT::decode($token, new Key($this->secret, 'HS256'));
        } catch (\UnexpectedValueException | \DomainException | InvalidArgumentException | \TypeError) {
            throw $this->unauthenticated();
        } finally {
            JWT::$timestamp = $timestamp;
            JWT::$leeway = $leeway;
        }
        if (!is_string($claims->sub ?? null) || !preg_match(self::UUID_PATTERN, $claims->sub)
            || !is_string($claims->role ?? null) || ($role = UserRole::tryFrom($claims->role)) === null
            || !is_int($claims->iat ?? null) || !is_int($claims->exp ?? null)
            || $claims->iat < 0 || $claims->iat > $now || $claims->exp <= $now
            || $claims->exp - $claims->iat !== self::LIFETIME) {
            throw $this->unauthenticated();
        }

        return new AuthenticatedIdentity($claims->sub, $role);
    }

    private function unauthenticated(): DomainViolation
    {
        return new DomainViolation('UNAUTHENTICATED', 'La sesión no es válida o ha vencido.');
    }

    public function __debugInfo(): array { return ['algorithm' => 'HS256']; }
}
