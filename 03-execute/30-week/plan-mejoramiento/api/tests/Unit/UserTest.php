<?php

declare(strict_types=1);

namespace Tests\Unit;

use Lab\Domain\DomainViolation;
use Lab\Domain\User;
use Lab\Domain\UserRole;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function test_username_is_trimmed_and_lowercased_including_unicode(): void
    {
        $user = new User('user-1', '  ÁNGELA.LAB  ', 'test-hash', 'lab_technician');
        self::assertSame('ángela.lab', $user->username);
        self::assertSame(UserRole::LabTechnician, $user->role);
        self::assertSame('ángela.lab', User::normalizeUsername(' Ángela.LAB '));
    }

    public function test_admin_is_representable_for_environment_provisioning(): void
    {
        self::assertSame(UserRole::Admin, (new User('user-1', 'admin', 'test-hash', 'admin'))->role);
    }

    public function test_hash_is_available_only_by_explicit_access_and_absent_from_json_and_debug(): void
    {
        $user = new User('user-1', 'operator', 'test-hash-private', 'lab_technician');
        self::assertSame('test-hash-private', $user->passwordHash());
        self::assertSame(['id' => 'user-1', 'username' => 'operator', 'role' => 'lab_technician'],
            json_decode(json_encode($user, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR));
        self::assertArrayNotHasKey('passwordHash', get_object_vars($user));
        ob_start();
        var_dump($user);
        $debug = ob_get_clean();
        self::assertStringNotContainsString('test-hash-private', $debug);
    }

    #[DataProvider('invalidRoles')]
    public function test_invalid_roles_are_rejected(string $role): void
    {
        $this->expectException(DomainViolation::class);
        new User('user-1', 'operator', 'test-hash', $role);
    }

    public static function invalidRoles(): array
    {
        return [[''], ['researcher'], ['ADMIN'], [' lab_technician ']];
    }

    #[DataProvider('invalidUsernames')]
    public function test_invalid_usernames_are_rejected(string $username): void
    {
        $this->expectException(DomainViolation::class);
        new User('user-1', $username, 'test-hash', 'lab_technician');
    }

    public static function invalidUsernames(): array { return [[' '], [str_repeat('a', 121)]]; }

    public function test_username_accepts_the_maximum_character_length(): void
    {
        self::assertSame(str_repeat('á', 120), (new User('user-1', str_repeat('Á', 120), 'test-hash', 'lab_technician'))->username);
    }

    #[DataProvider('invalidHashes')]
    public function test_missing_or_oversized_hash_is_rejected(string $hash): void
    {
        $this->expectException(DomainViolation::class);
        new User('user-1', 'operator', $hash, 'lab_technician');
    }

    public static function invalidHashes(): array { return [[''], [' '], [str_repeat('x', 513)]]; }
}
