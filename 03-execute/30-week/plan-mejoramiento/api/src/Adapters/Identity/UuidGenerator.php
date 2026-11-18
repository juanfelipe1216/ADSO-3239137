<?php

declare(strict_types=1);

namespace Lab\Adapters\Identity;

use Illuminate\Support\Str;
use Lab\Application\Ports\IdGenerator;

final class UuidGenerator implements IdGenerator
{
    public function generate(): string
    {
        return (string) Str::uuid();
    }
}
