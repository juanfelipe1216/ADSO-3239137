<?php

declare(strict_types=1);

namespace Lab\Application\Services;

use DateTimeZone;
use Lab\Application\DTOs\OverdueLine;
use Lab\Application\Ports\Clock;
use Lab\Application\Ports\OverdueReader;

final readonly class ListOverdueService
{
    public function __construct(private OverdueReader $reader, private Clock $clock) {}

    public function execute(): array
    {
        $now = $this->clock->now()->setTimezone(new DateTimeZone('UTC'));
        return ['items' => array_map(static fn (OverdueLine $line): array => $line->toArray($now), $this->reader->findOverdue($now))];
    }
}
