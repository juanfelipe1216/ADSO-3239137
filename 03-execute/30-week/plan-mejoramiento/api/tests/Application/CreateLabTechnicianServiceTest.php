<?php

declare(strict_types=1);

namespace Tests\Application;

use Lab\Application\Ports\IdGenerator;
use Lab\Application\Ports\UserRepository;
use Lab\Application\Services\CreateLabTechnicianService;
use Lab\Domain\DomainViolation;
use Lab\Domain\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\FakePasswordHasher;
use Tests\Fakes\InMemoryUserRepository;

final class CreateLabTechnicianServiceTest extends TestCase
{
    private InMemoryUserRepository $users;
    private FakePasswordHasher $passwords;
    private IdGenerator $ids;
    private CreateLabTechnicianService $service;
    private User $admin;

    protected function setUp(): void
    {
        $this->users = new InMemoryUserRepository();
        $this->passwords = new FakePasswordHasher();
        $this->ids = new class implements IdGenerator {
            public int $calls = 0;
            public function generate(): string
            {
                $this->calls++;
                return 'aaaaaaaa-aaaa-4aaa-8aaa-' . str_pad((string) $this->calls, 12, '0', STR_PAD_LEFT);
            }
        };
        $this->admin = new User('admin-id', 'admin', 'fixture-admin-hash', 'admin');
        $this->service = new CreateLabTechnicianService($this->users, $this->passwords, $this->ids);
    }

    public function test_admin_creates_normalized_technician_without_exposing_credentials(): void
    {
        $password = '  clave de prueba  ';
        $user = $this->service->execute($this->admin, ' ÁNGELA.LAB ', $password, 'lab_technician');

        self::assertSame($user, $this->users->users['ángela.lab']);
        self::assertSame([$password], $this->passwords->hashedPasswords);
        self::assertNotSame($password, $user->passwordHash());
        self::assertTrue($this->passwords->verify($password, $user->passwordHash()));
        self::assertSame([
            'id' => 'aaaaaaaa-aaaa-4aaa-8aaa-000000000001',
            'username' => 'ángela.lab',
            'role' => 'lab_technician',
        ], json_decode(json_encode($user, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR));
    }

    public function test_technician_is_rejected_before_validation_and_repository_access(): void
    {
        $actor = new User('operator-id', 'operator', 'fixture-hash', 'lab_technician');
        $this->assertViolation('FORBIDDEN_ROLE', fn () => $this->service->execute($actor, '', '', 'admin'));
        self::assertSame([], $this->users->lookups);
        $this->assertNoWrites();
    }

    #[DataProvider('forbiddenRoles')]
    public function test_admin_cannot_create_an_admin_or_an_unknown_role(string $role): void
    {
        $this->assertViolation('VALIDATION_ERROR',
            fn () => $this->service->execute($this->admin, 'operator', 'clave de prueba', $role));
        self::assertSame([], $this->users->lookups);
        $this->assertNoWrites();
    }

    public static function forbiddenRoles(): array { return [['admin'], ['researcher'], ['LAB_TECHNICIAN'], ['']]; }

    #[DataProvider('invalidPasswords')]
    public function test_password_length_is_validated_without_writes(string $password): void
    {
        $this->assertViolation('VALIDATION_ERROR',
            fn () => $this->service->execute($this->admin, 'operator', $password, 'lab_technician'));
        self::assertSame([], $this->users->lookups);
        $this->assertNoWrites();
    }

    public static function invalidPasswords(): array { return [[''], [str_repeat('a', 11)], [str_repeat('á', 129)]]; }

    #[DataProvider('validPasswords')]
    public function test_password_boundaries_count_characters_and_preserve_content(string $password): void
    {
        $user = $this->service->execute($this->admin, 'operator', $password, 'lab_technician');
        self::assertSame([$password], $this->passwords->hashedPasswords);
        self::assertTrue($this->passwords->verify($password, $user->passwordHash()));
    }

    public static function validPasswords(): array { return [[str_repeat('a', 12)], [str_repeat('á', 128)]]; }

    #[DataProvider('invalidUsernames')]
    public function test_invalid_username_has_no_side_effects(string $username): void
    {
        $this->assertViolation('VALIDATION_ERROR',
            fn () => $this->service->execute($this->admin, $username, 'clave de prueba', 'lab_technician'));
        self::assertSame([], $this->users->lookups);
        $this->assertNoWrites();
    }

    public static function invalidUsernames(): array { return [[' '], [str_repeat('a', 121)]]; }

    public function test_duplicate_normalized_username_is_rejected_without_replacing_user(): void
    {
        $existing = new User('existing-id', 'ángela.lab', 'existing-fixture-hash', 'lab_technician');
        $this->users->add($existing);
        $this->assertViolation('USERNAME_TAKEN',
            fn () => $this->service->execute($this->admin, ' ÁNGELA.LAB ', 'clave de prueba', 'lab_technician'));

        self::assertSame(['ángela.lab'], $this->users->lookups);
        self::assertSame(['ángela.lab' => $existing], $this->users->users);
        self::assertSame([], $this->passwords->hashedPasswords);
        self::assertSame(0, $this->ids->calls);
    }

    public function test_insert_conflict_is_propagated_when_username_becomes_taken_after_lookup(): void
    {
        $repository = new class implements UserRepository {
            public function findByUsername(string $username): ?User { return null; }
            public function add(User $user): void
            {
                throw new DomainViolation('USERNAME_TAKEN', 'El nombre de usuario ya está registrado.');
            }
        };
        $service = new CreateLabTechnicianService($repository, $this->passwords, $this->ids);
        $this->assertViolation('USERNAME_TAKEN',
            fn () => $service->execute($this->admin, 'operator', 'clave de prueba', 'lab_technician'));
    }

    public function test_fake_repository_rejects_duplicate_insert_without_overwriting(): void
    {
        $first = new User('first-id', 'operator', 'first-fixture-hash', 'lab_technician');
        $this->users->add($first);
        $this->assertViolation('USERNAME_TAKEN',
            fn () => $this->users->add(new User('second-id', ' OPERATOR ', 'second-fixture-hash', 'lab_technician')));
        self::assertSame(['operator' => $first], $this->users->users);
    }

    private function assertNoWrites(): void
    {
        self::assertSame([], $this->users->users);
        self::assertSame([], $this->passwords->hashedPasswords);
        self::assertSame(0, $this->ids->calls);
    }

    private function assertViolation(string $code, callable $operation): void
    {
        try {
            $operation();
            self::fail('Se aceptó una operación que debía rechazarse.');
        } catch (DomainViolation $error) {
            self::assertSame($code, $error->errorCode);
        }
    }
}
