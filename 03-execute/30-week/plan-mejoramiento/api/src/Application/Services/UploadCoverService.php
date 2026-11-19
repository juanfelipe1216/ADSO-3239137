<?php

declare(strict_types=1);

namespace Lab\Application\Services;

use Lab\Application\DTOs\CoverImage;
use Lab\Application\DTOs\EquipmentData;
use Lab\Application\Ports\CoverStorage;
use Lab\Application\Ports\EquipmentRepository;
use Lab\Domain\DomainViolation;
use Lab\Domain\User;
use Lab\Application\DTOs\AuthenticatedIdentity;
use Lab\Domain\UserRole;

final readonly class UploadCoverService
{
    public function __construct(private EquipmentRepository $equipment, private CoverStorage $storage) {}

    public function execute(User|AuthenticatedIdentity $actor, string $id, string $bytes): EquipmentData
    {
        if ($actor->role !== UserRole::Admin) {
            throw new DomainViolation('FORBIDDEN_ROLE', 'Solo el administrador puede cambiar fotografías.');
        }
        $stored = $this->equipment->findActive($id)
            ?? throw new DomainViolation('NOT_FOUND', 'El equipo no existe o está dado de baja.');
        $key = $this->storage->put(new CoverImage($bytes));
        $changed = $stored->equipment->withCover($key);
        try {
            $this->equipment->save($changed, $stored->version);
        } catch (\Throwable $error) {
            $this->storage->delete($key);
            throw $error;
        }
        if ($stored->equipment->coverKey !== null) {
            $this->storage->delete($stored->equipment->coverKey);
        }

        return new EquipmentData($changed);
    }
}
