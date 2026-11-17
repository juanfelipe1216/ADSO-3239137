<?php

declare(strict_types=1);

namespace Lab\Adapters\Persistence;

use Illuminate\Database\ConnectionInterface;
use Lab\Application\Ports\CategoryReader;

final readonly class PostgresCategories implements CategoryReader
{
    public function __construct(private ConnectionInterface $db) {}
    public function findName(string $id): ?string
    {
        return $this->db->selectOne('SELECT name FROM lab_equipment.category WHERE id = ?', [$id])?->name;
    }
    public function all(): array
    {
        return array_map(static fn ($row): array => (array) $row,
            $this->db->select('SELECT id, name FROM lab_equipment.category ORDER BY name COLLATE "C", id'));
    }
}
