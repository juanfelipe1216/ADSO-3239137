<?php

declare(strict_types=1);

namespace Tests\Unit;

use Lab\Domain\DomainViolation;
use Lab\Domain\ValueObjects\Reference;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ReferenceTest extends TestCase
{
    public function test_reference_is_normalized_without_an_isbn_constraint(): void
    {
        self::assertSame('LAB-01/A', (new Reference(' lab-01/a '))->value);
        self::assertSame('A', (new Reference('a'))->value);
        self::assertSame(str_repeat('A', 64), (new Reference(str_repeat('A', 64)))->value);
    }

    #[DataProvider('invalidReferences')]
    public function test_invalid_reference_is_rejected(string $value): void
    {
        $this->expectException(DomainViolation::class);
        new Reference($value);
    }

    public static function invalidReferences(): array
    {
        return [[''], ['   '], ['-LAB'], ['LAB 1'], ['ÓPTICA'], [str_repeat('A', 65)]];
    }
}
