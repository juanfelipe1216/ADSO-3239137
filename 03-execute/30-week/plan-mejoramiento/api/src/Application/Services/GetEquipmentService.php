<?php

declare(strict_types=1);

namespace Lab\Application\Services;

use Lab\Application\DTOs\EquipmentData;
use Lab\Application\Ports\EquipmentRepository;
use Lab\Domain\DomainViolation;

final readonly class GetEquipmentService
{
    public function __construct(private EquipmentRepository $equipment) {}

    public function execute(string $id): EquipmentData
    {
        $stored = $this->equipment->findActive($id)
            ?? throw new DomainViolation('NOT_FOUND', 'El equipo no existe o está dado de baja.');

        return new EquipmentData($stored->equipment);
    }
}
