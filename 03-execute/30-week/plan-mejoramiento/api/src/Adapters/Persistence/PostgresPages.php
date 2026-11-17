<?php

declare(strict_types=1);

namespace Lab\Adapters\Persistence;

use Illuminate\Database\ConnectionInterface;
use Lab\Application\DTOs\Page;
use Lab\Application\DTOs\Pagination;

final class PostgresPages
{
    public static function pattern(string $query): string
    {
        return '%' . strtr($query, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
    }

    /** SQL and order are internal adapter constants, never client input. */
    public static function read(ConnectionInterface $db, string $sql, array $bindings, string $order, Pagination $pagination): Page
    {
        if (!in_array($order, ['name', 'full_name'], true)) {
            throw new \LogicException('Unsupported page ordering.');
        }
        $row = $db->selectOne('WITH matches AS MATERIALIZED (' . $sql . '),
            paged AS (SELECT * FROM matches ORDER BY ' . $order . ' COLLATE "C", id LIMIT ? OFFSET ?)
            SELECT (SELECT count(*) FROM matches) AS total,
            COALESCE((SELECT jsonb_agg(to_jsonb(paged) ORDER BY ' . $order . ' COLLATE "C", id) FROM paged), \'[]\'::jsonb) AS items',
            [...$bindings, $pagination->size, $pagination->offset()]);
        return new Page(json_decode($row->items, true, flags: JSON_THROW_ON_ERROR), (int) $row->total, $pagination);
    }
}
