<?php

declare(strict_types=1);

namespace Tests\Unit;

use Lab\Domain\DomainViolation;
use Lab\Domain\ValueObjects\Quantity;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class QuantityTest extends TestCase
{
    public function test_positive_quantity_is_accepted(): void
    {
        self::assertSame(1, (new Quantity(1))->value);
    }

    #[DataProvider('invalidQuantities')]
    public function test_nonpositive_quantity_is_rejected(int $value): void
    {
        $this->expectException(DomainViolation::class);
        new Quantity($value);
    }

    public static function invalidQuantities(): array { return [[0], [-1]]; }
}
