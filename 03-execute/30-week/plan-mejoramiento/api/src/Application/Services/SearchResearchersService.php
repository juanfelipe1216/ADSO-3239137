<?php

declare(strict_types=1);

namespace Lab\Application\Services;

use Lab\Application\DTOs\Page;
use Lab\Application\DTOs\Pagination;
use Lab\Application\Ports\ResearcherReader;

final readonly class SearchResearchersService
{
    public function __construct(private ResearcherReader $researchers) {}

    public function execute(string $query = '', int $page = 1, int $size = 20): Page
    {
        return $this->researchers->search($query, new Pagination($page, $size));
    }
}
