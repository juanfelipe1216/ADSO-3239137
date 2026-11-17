<?php

declare(strict_types=1);

namespace Tests\Application;

use Tests\Fakes\FakePasswordHasher;
use Lab\Application\Ports\TokenIssuer;
use Tests\Fakes\InMemoryUserRepository;
use Lab\Application\Services\LoginService;
use Lab\Domain\DomainViolation;
use Lab\Domain\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LoginServiceTest extends TestCase
{
    private InMemoryUserRepository $users;
    private FakePasswordHasher $passwords;
    private TokenIssuer $tokens;
    private LoginService $service;

    protected function setUp(): void
    {
        $this->users = new InMemoryUserRepository();
        $this->passwords = new FakePasswordHasher(['fixture-hash' => '  clave de prueba  ']);
        $this->tokens = new class implements TokenIssuer {
            public array $issued = [];

            public function issue(User $user, int $lifetimeSeconds): string
            {
                $this->issued[] = [$user->id, $user->role->value, $lifetimeSeconds];
                return 'fixture-token';
            }
        };
        $this->service = new LoginService($this->users, $this->passwords, $this->tokens);
    }

    #[DataProvider('roles')]
    public function test_login_normalizes_username_and_requests_an_eight_hour_token(string $role): void
    {
        $this->users->add(new User('user-1', 'ángela.lab', 'fixture-hash', $role));
        $result = $this->service->execute('  ÁNGELA.LAB  ', '  clave de prueba  ');

        self::assertSame(['ángela.lab'], $this->users->lookups);
        self::assertSame([['  clave de prueba  ', 'fixture-hash']], $this->passwords->checks);
        self::assertSame([['user-1', $role, 28800]], $this->tokens->issued);
        self::assertSame([
            'access_token' => 'fixture-token', 'token_type' => 'Bearer', 'expires_in' => 28800, 'role' => $role,
        ], json_decode(json_encode($result, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR));
    }

    public static function roles(): array { return [['admin'], ['lab_technician']]; }

    public function test_unknown_user_and_wrong_password_have_the_same_error(): void
    {
        $this->users->add(new User('user-1', 'operator', 'fixture-hash', 'lab_technician'));
        $errors = [];
        foreach (['operator', 'unknown'] as $username) {
            try {
                $this->service->execute($username, 'incorrecta');
                self::fail('Se aceptaron credenciales incorrectas.');
            } catch (DomainViolation $error) {
                $errors[] = [$error->errorCode, $error->getMessage()];
            }
        }

        self::assertSame(['INVALID_CREDENTIALS', 'Usuario o contraseña incorrectos.'], $errors[0]);
        self::assertSame($errors[0], $errors[1]);
        self::assertSame([['incorrecta', 'fixture-hash'], ['incorrecta', null]], $this->passwords->checks);
        self::assertSame([], $this->tokens->issued);
    }

    public function test_password_is_not_trimmed_to_make_it_match(): void
    {
        $this->users->add(new User('user-1', 'operator', 'fixture-hash', 'lab_technician'));
        try {
            $this->service->execute('operator', 'clave de prueba');
            self::fail('Se transformó la contraseña.');
        } catch (DomainViolation $error) {
            self::assertSame('INVALID_CREDENTIALS', $error->errorCode);
            self::assertSame([], $this->tokens->issued);
        }
    }

    #[DataProvider('invalidUsernames')]
    public function test_invalid_username_is_rejected_before_repository_access(string $username): void
    {
        try {
            $this->service->execute($username, '  clave de prueba  ');
            self::fail('Se aceptó un usuario con formato inválido.');
        } catch (DomainViolation $error) {
            self::assertSame('VALIDATION_ERROR', $error->errorCode);
            self::assertSame([], $this->users->lookups);
            self::assertSame([], $this->passwords->checks);
            self::assertSame([], $this->tokens->issued);
        }
    }

    public static function invalidUsernames(): array { return [[' '], [str_repeat('a', 121)]]; }
}
