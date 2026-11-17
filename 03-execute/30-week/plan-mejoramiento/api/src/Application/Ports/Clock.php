<?php

declare(strict_types=1);

namespace Lab\Application\Ports;

use DateTimeImmutable;

interface Clock
{
    public function now(): DateTimeImmutable;
}
