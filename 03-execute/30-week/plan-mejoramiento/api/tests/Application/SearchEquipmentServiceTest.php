<?php

declare(strict_types=1);

namespace Tests\Application;

use DateTimeImmutable;
use Lab\Application\Services\SearchEquipmentService;
use Lab\Domain\DomainViolation;
use Lab\Domain\Equipment;
use Lab\Domain\ValueObjects\Quantity;
use Lab\Domain\ValueObjects\Reference;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemoryEquipmentRepository;

final class SearchEquipmentServiceTest extends TestCase
{
    private SearchEquipmentService $service;

    protected function setUp(): void
    {
        $repository = new InMemoryEquipmentRepository();
        foreach ([['b', 'Medidor', 'Acme', 'cat-a'], ['a', 'Medidor', 'Acme', 'cat-b'],
            ['c', 'Óptica_100%', 'Otro', 'cat-a'], ['d', 'Retirado', 'Acme', 'cat-a']] as [$id, $name, $manufacturer, $category]) {
            $item = Equipment::create($id, $name, $manufacturer, new Reference($id), $category, 'categoría', new Quantity(1));
            $repository->add($item);
            if ($id === 'd') {
                $repository->save($item->retire(new DateTimeImmutable('2026-11-16T00:00:00Z')), '1');
            }
        }
        $this->service = new SearchEquipmentService($repository);
    }

    public function test_search_by_manufacturer_uses_stable_order_and_excludes_retired(): void
    {
        $result = $this->service->execute('aCmE');
        self::assertSame(['a', 'b'], array_column($result->items, 'id'));
        self::assertSame(2, $result->total);
    }

    public function test_category_and_text_filters_combine(): void
    {
        self::assertSame(['b'], array_column($this->service->execute('med', 'cat-a')->items, 'id'));
        self::assertSame(['c'], array_column($this->service->execute('ÓPT', 'cat-a')->items, 'id'));
        self::assertSame(0, $this->service->execute('', 'missing')->total);
    }

    public function test_empty_page_preserves_total_and_requested_pagination(): void
    {
        self::assertSame(['b'], array_column($this->service->execute('', null, 2, 1)->items, 'id'));
        self::assertSame(['items' => [], 'page' => 4, 'size' => 1, 'total' => 3],
            $this->service->execute('', null, 4, 1)->jsonSerialize());
    }

    public function test_wildcards_are_literal_and_not_sql_patterns(): void
    {
        self::assertSame(['c'], array_column($this->service->execute('%')->items, 'id'));
        self::assertSame(['c'], array_column($this->service->execute('_')->items, 'id'));
    }

    public function test_page_size_is_rejected_instead_of_silently_clamped(): void
    {
        $this->expectException(DomainViolation::class);
        $this->service->execute('', null, 1, 51);
    }
}
