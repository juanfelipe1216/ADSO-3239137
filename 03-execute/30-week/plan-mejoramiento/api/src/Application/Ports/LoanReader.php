<?php

declare(strict_types=1);

namespace Lab\Application\Ports;

use DateTimeImmutable;
use Lab\Application\DTOs\LoanFilter;
use Lab\Application\DTOs\Page;
use Lab\Application\DTOs\Pagination;

interface LoanReader
{
    public function search(LoanFilter $filter, Pagination $pagination, DateTimeImmutable $now): Page;
}
