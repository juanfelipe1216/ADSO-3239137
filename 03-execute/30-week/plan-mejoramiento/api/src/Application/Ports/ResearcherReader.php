<?php

declare(strict_types=1);

namespace Lab\Application\Ports;

use Lab\Application\DTOs\Page;
use Lab\Application\DTOs\Pagination;
use Lab\Application\DTOs\ResearcherDetails;

interface ResearcherReader
{
    /** Search name or document literally and case-insensitively; order by full_name then id. */
    public function search(string $query, Pagination $pagination): Page;

    /** Count outstanding lines, including overdue ones, rather than loan headers. */
    public function findById(string $id): ?ResearcherDetails;
}
