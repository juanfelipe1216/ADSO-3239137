<?php

declare(strict_types=1);

namespace Tests\Unit;

use Lab\Domain\DomainViolation;
use Lab\Domain\Researcher;
use Lab\Domain\ValueObjects\DocumentNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ResearcherTest extends TestCase
{
    public function test_name_is_trimmed_and_document_keeps_leading_zeroes(): void
    {
        $researcher = new Researcher('researcher-1', '  Ana María  ', new DocumentNumber(' 001234 '));
        self::assertSame('Ana María', $researcher->fullName);
        self::assertSame('001234', $researcher->documentNumber->value);
        self::assertSame(['fullName', 'id', 'documentNumber'], array_keys(get_object_vars($researcher)));
    }

    public function test_name_accepts_two_hundred_unicode_characters(): void
    {
        self::assertSame(str_repeat('é', 200), (new Researcher('id', str_repeat('é', 200), new DocumentNumber('1')))->fullName);
    }

    #[DataProvider('invalidNames')]
    public function test_empty_or_oversized_name_is_rejected(string $name): void
    {
        $this->expectException(DomainViolation::class);
        new Researcher('id', $name, new DocumentNumber('1'));
    }

    public static function invalidNames(): array { return [[''], ['  '], [str_repeat('é', 201)]]; }
}
