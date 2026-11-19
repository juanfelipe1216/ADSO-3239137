<?php

declare(strict_types=1);

namespace Lab\Application\Services;

use Lab\Application\DTOs\LoanFilter;
use Lab\Application\DTOs\Page;
use Lab\Application\DTOs\Pagination;
use Lab\Application\Ports\Clock;
use Lab\Application\Ports\LoanReader;

final readonly class SearchLoansService
{
    public function __construct(private LoanReader $reader, private Clock $clock) {}

    public function execute(?string $from, ?string $to, ?string $status, ?string $researcherId, int $page = 1, int $size = 20): Page
    {
        return $this->reader->search(new LoanFilter($from, $to, $status, $researcherId), new Pagination($page, $size), $this->clock->now());
    }
}
