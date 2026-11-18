<?php

declare(strict_types=1);

namespace Lab\Application\Ports;

use DateTimeImmutable;
use Lab\Application\DTOs\OverdueLine;

interface OverdueReader
{
    /**
     * Select only unreturned lines with due_at < now, ordered by due_at then item id ascending.
     * Use frozen equipment data and the current researcher name; never substitute live catalog data.
     * @return list<OverdueLine>
     */
    public function findOverdue(DateTimeImmutable $now): array;
}
