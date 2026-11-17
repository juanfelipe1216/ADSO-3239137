<?php

declare(strict_types=1);

namespace Lab\Adapters\Persistence;

use Illuminate\Database\ConnectionInterface;
use Lab\Application\DTOs\Page;
use Lab\Application\DTOs\Pagination;
use Lab\Application\DTOs\StoredEquipment;
use Lab\Application\Ports\EquipmentRepository;
use Lab\Application\Ports\EquipmentReader;
use Lab\Domain\DomainViolation;
use Lab\Domain\Equipment;
use Lab\Domain\ValueObjects\Quantity;
use Lab\Domain\ValueObjects\Reference;

final readonly class PostgresEquipmentRepository implements EquipmentRepository, EquipmentReader
{
    public function __construct(private ConnectionInterface $db) {}

    public function findActive(string $id): ?StoredEquipment
    {
        $row = $this->db->selectOne('SELECT e.*, e.xmin::text AS version, c.name AS category_name
            FROM lab_equipment.equipment e JOIN lab_equipment.category c ON c.id = e.category_id
            WHERE e.id = ? AND e.deleted_at IS NULL', [$id]);
        return $row === null ? null : new StoredEquipment(new Equipment($row->id, $row->name, $row->manufacturer,
            new Reference($row->reference), $row->category_id, $row->category_name, new Quantity((int) $row->total_units),
            (int) $row->available_units, $row->cover_key), $row->version);
    }

    public function referenceExists(string $reference): bool
    {
        return $this->db->selectOne('SELECT id FROM lab_equipment.equipment WHERE reference = ? AND deleted_at IS NULL', [$reference]) !== null;
    }

    public function add(Equipment $equipment): void
    {
        $row = $this->db->selectOne('INSERT INTO lab_equipment.equipment
            (id, name, manufacturer, reference, category_id, total_units, available_units, cover_key, deleted_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON CONFLICT (reference) WHERE deleted_at IS NULL DO NOTHING RETURNING id',
            [$equipment->id, $equipment->name, $equipment->manufacturer, $equipment->reference->value,
                $equipment->categoryId, $equipment->totalUnits->value, $equipment->availableUnits,
                $equipment->coverKey, $equipment->deletedAt?->format('Y-m-d H:i:s.uP')]);
        if ($row === null) {
            throw new DomainViolation('REFERENCE_TAKEN', 'La referencia ya está registrada.');
        }
    }

    public function save(Equipment $equipment, string $expectedVersion): void
    {
        $count = $this->db->update('UPDATE lab_equipment.equipment SET name = ?, manufacturer = ?, category_id = ?,
            total_units = ?, available_units = ?, cover_key = ?, deleted_at = ?
            WHERE id = ? AND xmin::text = ? AND deleted_at IS NULL AND reference = ?',
            [$equipment->name, $equipment->manufacturer, $equipment->categoryId, $equipment->totalUnits->value,
                $equipment->availableUnits, $equipment->coverKey, $equipment->deletedAt?->format('Y-m-d H:i:s.uP'),
                $equipment->id, $expectedVersion, $equipment->reference->value]);
        if ($count !== 1) {
            throw new DomainViolation('EQUIPMENT_NOT_AVAILABLE', 'El equipo cambió; vuelve a consultar sus datos.');
        }
    }

    public function search(string $query, ?string $categoryId, Pagination $pagination): Page
    {
        $pattern = PostgresPages::pattern($query);
        return PostgresPages::read($this->db,
            "SELECT e.id, e.name, e.manufacturer, e.reference, e.total_units, e.available_units, e.cover_key,
            jsonb_build_object('id', c.id, 'name', c.name) AS category
            FROM lab_equipment.equipment e JOIN lab_equipment.category c ON c.id = e.category_id
            WHERE e.deleted_at IS NULL AND (CAST(? AS uuid) IS NULL OR e.category_id = CAST(? AS uuid))
            AND (e.name ILIKE ? ESCAPE '!' OR e.manufacturer ILIKE ? ESCAPE '!')",
            [$categoryId, $categoryId, $pattern, $pattern], 'name', $pagination);
    }
}
