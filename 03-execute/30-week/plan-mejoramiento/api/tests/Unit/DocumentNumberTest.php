<?php

declare(strict_types=1);

namespace Tests\Unit;

use Lab\Domain\DomainViolation;
use Lab\Domain\ValueObjects\DocumentNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DocumentNumberTest extends TestCase
{
    public function test_document_is_trimmed_and_preserves_leading_zeroes(): void
    {
        self::assertSame('001234', (new DocumentNumber(' 001234 '))->value);
        self::assertSame(str_repeat('1', 30), (new DocumentNumber(str_repeat('1', 30)))->value);
    }

    #[DataProvider('invalidDocuments')]
    public function test_invalid_document_is_rejected(string $value): void
    {
        $this->expectException(DomainViolation::class);
        new DocumentNumber($value);
    }

    public static function invalidDocuments(): array { return [[''], ['  '], [str_repeat('1', 31)]]; }
}
