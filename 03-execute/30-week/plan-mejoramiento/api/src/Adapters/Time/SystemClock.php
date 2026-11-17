<?php

declare(strict_types=1);

namespace Lab\Adapters\Time;

use DateTimeImmutable;
use DateTimeZone;
use Lab\Application\Ports\Clock;

final readonly class SystemClock implements Clock
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
