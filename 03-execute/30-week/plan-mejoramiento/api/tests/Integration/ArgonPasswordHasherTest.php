<?php

declare(strict_types=1);

namespace Tests\Integration;

use Lab\Adapters\Security\ArgonPasswordHasher;
use PHPUnit\Framework\TestCase;

final class ArgonPasswordHasherTest extends TestCase
{
    public function test_hash_is_argon2id_with_random_salt_and_verifies_exact_password(): void
    {
        $hasher = new ArgonPasswordHasher();
        $password = '  contraseña de prueba  ';
        $first = $hasher->hash($password);
        $second = $hasher->hash($password);
        self::assertSame('argon2id', password_get_info($first)['algoName']);
        self::assertNotSame($first, $second);
        self::assertNotSame($password, $first);
        self::assertTrue($hasher->verify($password, $first));
        self::assertFalse($hasher->verify(trim($password), $first));
        self::assertFalse($hasher->verify('incorrecta', $first));
    }

    public function test_long_unicode_password_is_not_truncated_at_seventy_two_bytes(): void
    {
        $hasher = new ArgonPasswordHasher();
        $password = str_repeat('á', 127) . 'A';
        $hash = $hasher->hash($password);
        self::assertTrue($hasher->verify($password, $hash));
        self::assertFalse($hasher->verify(str_repeat('á', 127) . 'B', $hash));
    }

    public function test_missing_or_invalid_hash_cannot_authenticate(): void
    {
        $hasher = new ArgonPasswordHasher();
        self::assertFalse($hasher->verify('clave de prueba', null));
        self::assertFalse($hasher->verify('clave de prueba', 'fixture-invalid-hash'));
        self::assertFalse($hasher->verify('clave de prueba', ''));
    }

    public function test_debug_representation_does_not_expose_dummy_hash(): void
    {
        $hasher = new ArgonPasswordHasher();
        ob_start();
        var_dump($hasher);
        $output = ob_get_clean();
        self::assertStringNotContainsString('$argon2id$', $output);
        self::assertStringNotContainsString('dummyHash', $output);
    }
}
