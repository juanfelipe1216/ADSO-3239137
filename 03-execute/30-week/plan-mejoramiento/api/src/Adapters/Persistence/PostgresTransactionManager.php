<?php

declare(strict_types=1);

namespace Lab\Adapters\Persistence;

use Illuminate\Database\ConnectionInterface;
use Lab\Application\Ports\TransactionManager;

final readonly class PostgresTransactionManager implements TransactionManager
{
    public function __construct(private ConnectionInterface $db) {}

    public function forResearcher(string $researcherId, callable $operation): mixed
    {
        return $this->db->transaction(function () use ($researcherId, $operation) {
            $this->db->selectOne('SELECT pg_advisory_xact_lock(hashtextextended(CAST(? AS uuid)::text, 0))', [$researcherId]);
            return $operation();
        });
    }
}
