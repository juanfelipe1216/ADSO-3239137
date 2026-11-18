<?php

declare(strict_types=1);

namespace Tests\Integration;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lab\Application\Ports\EquipmentRepository;
use Lab\Application\Ports\ResearcherRepository;
use Lab\Application\Ports\UserRepository;
use Lab\Domain\Equipment;
use Lab\Domain\Researcher;
use Lab\Domain\User;
use Lab\Domain\ValueObjects\DocumentNumber;
use Lab\Domain\ValueObjects\Quantity;
use Lab\Domain\ValueObjects\Reference;
use Tests\TestCase;

abstract class LoanDatabaseCase extends TestCase
{
    private array $users = [];
    private array $researchers = [];
    private array $equipment = [];

    protected function actor(): User
    {
        $id = (string) Str::uuid();
        $actor = new User($id, 'operator-' . $id, 'fixture-hash', 'lab_technician');
        app(UserRepository::class)->add($actor);
        $this->users[] = $id;
        return $actor;
    }

    protected function researcher(): string
    {
        $id = (string) Str::uuid();
        app(ResearcherRepository::class)->add(new Researcher($id, 'Investigador de prueba', new DocumentNumber(bin2hex(random_bytes(12)))));
        $this->researchers[] = $id;
        return $id;
    }

    protected function equipment(): string
    {
        $id = (string) Str::uuid();
        app(EquipmentRepository::class)->add(Equipment::create($id, 'Medidor', 'Fabricante',
            new Reference($id), '11111111-1111-4111-8111-111111111111', 'medición', new Quantity(1)));
        $this->equipment[] = $id;
        return $id;
    }

    protected function stock(string $id): int
    {
        return app(EquipmentRepository::class)->findActive($id)->equipment->availableUnits;
    }

    protected function tearDown(): void
    {
        DB::transaction(function () {
            DB::table('lab_equipment.loan')->whereIn('researcher_id', $this->researchers)->delete();
            DB::table('lab_equipment.equipment')->whereIn('id', $this->equipment)->delete();
            DB::table('lab_equipment.researcher')->whereIn('id', $this->researchers)->delete();
            DB::table('lab_equipment.user')->whereIn('id', $this->users)->delete();
        });
        parent::tearDown();
    }
}
