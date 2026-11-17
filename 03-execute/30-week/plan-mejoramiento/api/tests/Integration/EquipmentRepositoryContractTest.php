<?php

declare(strict_types=1);

namespace Tests\Integration;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lab\Application\DTOs\Pagination;
use Lab\Application\Ports\EquipmentRepository;
use Lab\Application\Ports\EquipmentReader;
use Lab\Application\Ports\CategoryReader;
use Lab\Domain\DomainViolation;
use Lab\Domain\Equipment;
use Lab\Domain\ValueObjects\Quantity;
use Lab\Domain\ValueObjects\Reference;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Fakes\InMemoryEquipmentRepository;
use Tests\Fakes\InMemoryCategories;
use Tests\TestCase;

final class EquipmentRepositoryContractTest extends TestCase
{
    private array $ids = [];
    private const CATEGORY = '11111111-1111-4111-8111-111111111111';

    protected function tearDown(): void
    {
        DB::table('lab_equipment.equipment')->whereIn('id', $this->ids)->delete();
        parent::tearDown();
    }

    private function item(?string $reference = null, string $name = 'Equipo', string $manufacturer = 'Fabricante'): Equipment
    {
        $id = (string) Str::uuid();
        $this->ids[] = $id;
        return Equipment::create($id, $name, $manufacturer, new Reference($reference ?? $id), self::CATEGORY, 'medición', new Quantity(2));
    }

    public static function backends(): array { return [['fake'], ['postgres']]; }

    #[DataProvider('backends')]
    public function test_round_trip_duplicate_stock_version_and_retirement(string $backend): void
    {
        $repository = $backend === 'fake' ? new InMemoryEquipmentRepository() : $this->app->make(EquipmentRepository::class);
        $item = $this->item();
        self::assertNull($repository->findActive($item->id));
        $repository->add($item);
        $first = $repository->findActive($item->id);
        self::assertSame($item->reference->value, $first->equipment->reference->value);
        self::assertSame(2, $first->equipment->availableUnits);
        self::assertTrue($repository->referenceExists($item->reference->value));
        $this->rejects('REFERENCE_TAKEN', fn () => $repository->add($this->item($item->reference->value)));
        $repository->save($first->equipment->withdraw()->withCover('opaque-key'), $first->version);
        $borrowed = $repository->findActive($item->id);
        self::assertNotSame($first->version, $borrowed->version);
        self::assertSame(1, $borrowed->equipment->availableUnits);
        self::assertSame('opaque-key', $borrowed->equipment->coverKey);
        $this->rejects('EQUIPMENT_NOT_AVAILABLE', fn () => $repository->save($first->equipment, $first->version));
        self::assertSame(1, $repository->findActive($item->id)->equipment->availableUnits);
        $repository->save($borrowed->equipment->restock(), $borrowed->version);
        $returned = $repository->findActive($item->id);
        $repository->save($returned->equipment->retire(new \DateTimeImmutable('2026-11-17T00:00:00Z')), $returned->version);
        self::assertNull($repository->findActive($item->id));
        self::assertFalse($repository->referenceExists($item->reference->value));
        $this->rejects('EQUIPMENT_NOT_AVAILABLE', fn () => $repository->save($returned->equipment, $returned->version));
        $replacement = $this->item($item->reference->value);
        $repository->add($replacement);
        self::assertNotNull($repository->findActive($replacement->id));
        if ($backend === 'postgres') {
            self::assertNotNull(DB::table('lab_equipment.equipment')->where('id', $item->id)->value('deleted_at'));
        }
    }

    public function test_search_matches_fake_for_filters_literal_patterns_and_empty_pages(): void
    {
        $fake = new InMemoryEquipmentRepository();
        $real = $this->app->make(EquipmentRepository::class);
        $reader = $this->app->make(EquipmentReader::class);
        $marker = 'contract-' . bin2hex(random_bytes(8));
        foreach (['Medidor', 'Medidor', $marker . '_100%', 'Retirado'] as $name) {
            $item = $this->item(null, $name, $marker);
            $fake->add($item);
            $real->add($item);
            if ($name === 'Retirado') {
                $fake->save($item->retire(new \DateTimeImmutable()), $fake->findActive($item->id)->version);
                $real->save($item->retire(new \DateTimeImmutable()), $real->findActive($item->id)->version);
            }
        }
        foreach ([[strtoupper($marker), null, 1], [$marker, self::CATEGORY, 2], [$marker, null, 5],
            [$marker . '_', null, 1], [$marker . '_100%', null, 1], [$marker, (string) Str::uuid(), 1], ["' OR 1=1 --", null, 1]] as [$q, $cat, $page]) {
            $pagination = new Pagination($page, 1);
            $expected = $fake->search($q, $cat, $pagination)->jsonSerialize();
            $actual = $reader->search($q, $cat, $pagination)->jsonSerialize();
            self::assertEquals($expected, $actual);
            self::assertSame(array_column($expected['items'], 'id'), array_column($actual['items'], 'id'));
            self::assertSame($expected['total'], $actual['total']);
        }
    }

    public function test_categories_match_fake_and_seed(): void
    {
        $real = $this->app->make(CategoryReader::class);
        $map = DB::table('lab_equipment.category')->pluck('name', 'id')->all();
        $fake = new InMemoryCategories($map);
        self::assertCount(5, $real->all());
        self::assertSame($fake->all(), $real->all());
        self::assertSame('medición', $real->findName(self::CATEGORY));
        self::assertNull($real->findName((string) Str::uuid()));
    }

    private function rejects(string $code, callable $operation): void
    {
        try { $operation(); self::fail('Se aceptó una escritura inválida.'); }
        catch (DomainViolation $error) { self::assertSame($code, $error->errorCode); }
    }
}
