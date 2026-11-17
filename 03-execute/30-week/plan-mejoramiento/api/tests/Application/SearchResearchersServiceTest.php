<?php

declare(strict_types=1);

namespace Tests\Application;

use Lab\Application\Services\SearchResearchersService;
use Lab\Domain\DomainViolation;
use Lab\Domain\Researcher;
use Lab\Domain\ValueObjects\DocumentNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemoryResearcherReader;

final class SearchResearchersServiceTest extends TestCase
{
    private InMemoryResearcherReader $reader;
    private SearchResearchersService $service;

    protected function setUp(): void
    {
        $this->reader = new InMemoryResearcherReader([
            new Researcher('id-c', 'Luis', new DocumentNumber('003')),
            new Researcher('id-b', 'Ana', new DocumentNumber('002')),
            new Researcher('id-a', 'Ana', new DocumentNumber('001')),
            new Researcher('id-d', 'Ángela', new DocumentNumber('004')),
        ]);
        $this->service = new SearchResearchersService($this->reader);
    }

    public function test_default_page_and_stable_order_for_equal_names(): void
    {
        $result = $this->service->execute()->jsonSerialize();
        self::assertSame(['id-a', 'id-b', 'id-c', 'id-d'], array_column($result['items'], 'id'));
        self::assertSame(['id', 'full_name', 'document_number'], array_keys($result['items'][0]));
        self::assertSame(1, $result['page']);
        self::assertSame(20, $result['size']);
        self::assertSame(4, $result['total']);
    }

    public function test_subsequent_and_empty_pages_keep_filtered_total(): void
    {
        $second = $this->service->execute('AN', 2, 1);
        self::assertSame(['id-b'], array_column($second->items, 'id'));
        self::assertSame(2, $second->total);
        self::assertSame(['items' => [], 'page' => 3, 'size' => 1, 'total' => 2],
            $this->service->execute('AN', 3, 1)->jsonSerialize());
    }

    public function test_search_supports_unicode_case_and_document_substrings(): void
    {
        self::assertSame(['id-d'], array_column($this->service->execute('ÁN')->items, 'id'));
        self::assertSame(['id-b'], array_column($this->service->execute('02')->items, 'id'));
        self::assertSame(0, $this->service->execute('unknown')->total);
    }

    public function test_percent_and_underscore_are_literal_search_characters(): void
    {
        $this->reader->researchers[] = new Researcher('id-e', 'Ana_100%', new DocumentNumber('005'));
        self::assertSame(['id-e'], array_column($this->service->execute('%')->items, 'id'));
        self::assertSame(['id-e'], array_column($this->service->execute('_')->items, 'id'));
    }

    #[DataProvider('invalidPagination')]
    public function test_invalid_pagination_is_rejected_before_reading(int $page, int $size): void
    {
        try {
            $this->service->execute('', $page, $size);
            self::fail('Se aceptó una paginación inválida.');
        } catch (DomainViolation $error) {
            self::assertSame('VALIDATION_ERROR', $error->errorCode);
            self::assertSame(0, $this->reader->searchCalls);
        }
    }

    public static function invalidPagination(): array { return [[0, 20], [-1, 20], [1, 0], [1, 51], [PHP_INT_MAX, 50]]; }

    public function test_maximum_page_size_is_accepted(): void
    {
        self::assertSame(50, $this->service->execute('', 1, 50)->pagination->size);
    }
}
