<?php

declare(strict_types=1);

namespace Tests\Fakes;

use DateTimeImmutable;
use Lab\Application\Ports\Clock;

final readonly class FrozenClock implements Clock
{
    public function __construct(private DateTimeImmutable $instant) {}
    public function now(): DateTimeImmutable { return $this->instant; }
}
