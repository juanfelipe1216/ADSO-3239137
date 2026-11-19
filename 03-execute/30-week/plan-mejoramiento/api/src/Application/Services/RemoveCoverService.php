<?php

declare(strict_types=1);

namespace Lab\Application\Services;

use Lab\Application\Ports\CoverStorage;
use Lab\Application\Ports\EquipmentRepository;
use Lab\Domain\DomainViolation;
use Lab\Domain\User;
use Lab\Application\DTOs\AuthenticatedIdentity;
use Lab\Domain\UserRole;

final readonly class RemoveCoverService
{
    public function __construct(private EquipmentRepository $equipment, private CoverStorage $storage) {}

    public function execute(User|AuthenticatedIdentity $actor, string $id): void
    {
        if ($actor->role !== UserRole::Admin) {
            throw new DomainViolation('FORBIDDEN_ROLE', 'Solo el administrador puede quitar fotografías.');
        }
        $stored = $this->equipment->findActive($id)
            ?? throw new DomainViolation('NOT_FOUND', 'El equipo no existe o está dado de baja.');
        $key = $stored->equipment->coverKey;
        if ($key === null) {
            return;
        }
        $this->equipment->save($stored->equipment->withCover(null), $stored->version);
        $this->storage->delete($key);
    }
}
