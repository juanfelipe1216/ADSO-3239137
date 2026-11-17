<?php

declare(strict_types=1);

namespace Lab\Application\Ports;

use Lab\Application\DTOs\Page;
use Lab\Application\DTOs\Pagination;

interface EquipmentReader
{
    /** Active rows only, literal case-insensitive name/manufacturer match, ordered by name then id. */
    public function search(string $query, ?string $categoryId, Pagination $pagination): Page;
}
