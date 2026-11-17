<?php

declare(strict_types=1);

namespace Tests\Fakes;

use Closure;
use Lab\Application\DTOs\EquipmentData;
use Lab\Application\DTOs\Page;
use Lab\Application\DTOs\Pagination;
use Lab\Application\DTOs\StoredEquipment;
use Lab\Application\Ports\EquipmentReader;
use Lab\Application\Ports\EquipmentRepository;
use Lab\Domain\DomainViolation;
use Lab\Domain\Equipment;

final class InMemoryEquipmentRepository implements EquipmentRepository, EquipmentReader
{
    /** @var array<string, StoredEquipment> */
    public array $rows = [];
    public array $lookups = [];
    public ?Closure $beforeSave = null;
    public ?Closure $beforeAdd = null;

    public function findActive(string $id): ?StoredEquipment
    {
        $this->lookups[] = $id;
        $stored = $this->rows[$id] ?? null;
        return $stored?->equipment->deletedAt === null ? $stored : null;
    }

    public function referenceExists(string $reference): bool
    {
        foreach ($this->rows as $stored) {
            if ($stored->equipment->deletedAt === null && $stored->equipment->reference->value === $reference) {
                return true;
            }
        }
        return false;
    }

    public function add(Equipment $equipment): void
    {
        if ($this->beforeAdd !== null) {
            $hook = $this->beforeAdd;
            $this->beforeAdd = null;
            $hook($this);
        }
        if ($this->referenceExists($equipment->reference->value)) {
            throw new DomainViolation('REFERENCE_TAKEN', 'La referencia ya está registrada.');
        }
        if (isset($this->rows[$equipment->id])) {
            throw new \LogicException('Duplicate equipment identifier in fixture.');
        }
        $this->rows[$equipment->id] = new StoredEquipment($equipment, '1');
    }

    public function save(Equipment $equipment, string $expectedVersion): void
    {
        if ($this->beforeSave !== null) {
            $hook = $this->beforeSave;
            $this->beforeSave = null;
            $hook($this);
        }
        $current = $this->rows[$equipment->id] ?? null;
        if ($current === null || $current->equipment->deletedAt !== null || $current->version !== $expectedVersion) {
            throw new DomainViolation('EQUIPMENT_NOT_AVAILABLE', 'El equipo cambió; vuelve a consultar sus datos.');
        }
        $this->rows[$equipment->id] = new StoredEquipment($equipment, (string) ((int) $expectedVersion + 1));
    }

    public function search(string $query, ?string $categoryId, Pagination $pagination): Page
    {
        $matches = [];
        foreach ($this->rows as $stored) {
            $item = $stored->equipment;
            if ($item->deletedAt === null && ($categoryId === null || $item->categoryId === $categoryId)
                && (mb_stripos($item->name, $query, 0, 'UTF-8') !== false || mb_stripos($item->manufacturer, $query, 0, 'UTF-8') !== false)) {
                $matches[] = $item;
            }
        }
        usort($matches, static fn (Equipment $left, Equipment $right): int => [$left->name, $left->id] <=> [$right->name, $right->id]);
        $items = array_map(static fn (Equipment $item): array => (new EquipmentData($item))->jsonSerialize(),
            array_slice($matches, $pagination->offset(), $pagination->size));
        return new Page($items, count($matches), $pagination);
    }
}
