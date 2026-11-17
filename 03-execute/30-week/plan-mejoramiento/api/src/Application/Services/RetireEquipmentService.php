<?php

declare(strict_types=1);

namespace Lab\Application\Services;

use Lab\Application\Ports\Clock;
use Lab\Application\Ports\EquipmentRepository;
use Lab\Domain\DomainViolation;
use Lab\Domain\User;
use Lab\Domain\UserRole;

final readonly class RetireEquipmentService
{
    public function __construct(private EquipmentRepository $equipment, private Clock $clock) {}

    public function execute(User $actor, string $id): void
    {
        if ($actor->role !== UserRole::Admin) {
            throw new DomainViolation('FORBIDDEN_ROLE', 'Solo el administrador puede dar de baja equipos.');
        }
        $stored = $this->equipment->findActive($id)
            ?? throw new DomainViolation('NOT_FOUND', 'El equipo no existe o está dado de baja.');
        $retired = $stored->equipment->retire($this->clock->now());
        $this->equipment->save($retired, $stored->version);
    }
}
