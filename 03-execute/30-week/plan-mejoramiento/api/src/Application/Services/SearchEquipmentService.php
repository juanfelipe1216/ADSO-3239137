<?php

declare(strict_types=1);

namespace Lab\Application\Services;

use Lab\Application\DTOs\Page;
use Lab\Application\DTOs\Pagination;
use Lab\Application\Ports\EquipmentReader;

final readonly class SearchEquipmentService
{
    public function __construct(private EquipmentReader $equipment) {}

    public function execute(string $query = '', ?string $categoryId = null, int $page = 1, int $size = 20): Page
    {
        return $this->equipment->search($query, $categoryId, new Pagination($page, $size));
    }
}
