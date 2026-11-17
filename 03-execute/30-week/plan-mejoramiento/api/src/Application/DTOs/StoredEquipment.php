<?php

declare(strict_types=1);

namespace Lab\Application\DTOs;

use Lab\Domain\Equipment;

final readonly class StoredEquipment
{
    public function __construct(public Equipment $equipment, public string $version) {}
}
