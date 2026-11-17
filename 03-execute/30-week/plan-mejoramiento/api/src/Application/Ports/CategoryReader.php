<?php

declare(strict_types=1);

namespace Lab\Application\Ports;

interface CategoryReader
{
    public function findName(string $id): ?string;

    /** @return list<array{id: string, name: string}> Ordered by name. */
    public function all(): array;
}
