<?php

declare(strict_types=1);

namespace Lab\Application\Ports;

use Lab\Application\DTOs\StoredEquipment;
use Lab\Domain\Equipment;

interface EquipmentRepository
{
    public function findActive(string $id): ?StoredEquipment;
    public function referenceExists(string $reference): bool;

    /** Reject active duplicate references with REFERENCE_TAKEN, including concurrent inserts. */
    public function add(Equipment $equipment): void;

    /** Atomically require an active row with the expected version; otherwise EQUIPMENT_NOT_AVAILABLE. */
    public function save(Equipment $equipment, string $expectedVersion): void;
}
