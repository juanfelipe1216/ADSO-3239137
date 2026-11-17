<?php

declare(strict_types=1);

namespace Lab\Application\Ports;

interface IdGenerator
{
    public function generate(): string;
}
