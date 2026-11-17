<?php

declare(strict_types=1);

namespace Lab\Application\Services;

use Lab\Application\Ports\CategoryReader;

final readonly class ListCategoriesService
{
    public function __construct(private CategoryReader $categories) {}

    public function execute(): array
    {
        return ['items' => $this->categories->all()];
    }
}
