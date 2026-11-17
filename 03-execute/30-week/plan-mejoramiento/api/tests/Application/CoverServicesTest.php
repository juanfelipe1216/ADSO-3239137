<?php

declare(strict_types=1);

namespace Tests\Application;

use Lab\Application\DTOs\CoverImage;
use Lab\Application\DTOs\StoredEquipment;
use Lab\Application\Services\UploadCoverService;
use Lab\Application\Services\RemoveCoverService;
use Lab\Application\Services\GetCoverService;
use Lab\Domain\DomainViolation;
use Lab\Domain\Equipment;
use Lab\Domain\User;
use Lab\Domain\ValueObjects\Quantity;
use Lab\Domain\ValueObjects\Reference;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\ImageFixture;
use Tests\Fakes\InMemoryCoverStorage;
use Tests\Fakes\InMemoryEquipmentRepository;

final class CoverServicesTest extends TestCase
{
    private InMemoryEquipmentRepository $equipment;
    private InMemoryCoverStorage $storage;
    private User $admin;
    private UploadCoverService $upload;
    private RemoveCoverService $remove;

    protected function setUp(): void
    {
        $this->equipment = new InMemoryEquipmentRepository();
        $this->equipment->add(Equipment::create('id', 'Equipo', 'Fabricante', new Reference('A'), 'cat', 'medición', new Quantity(1)));
        $this->storage = new InMemoryCoverStorage();
        $this->admin = new User('admin', 'admin', 'fixture-hash', 'admin');
        $this->upload = new UploadCoverService($this->equipment, $this->storage);
        $this->remove = new RemoveCoverService($this->equipment, $this->storage);
    }

    public function test_upload_replace_download_and_idempotent_removal(): void
    {
        $first = $this->upload->execute($this->admin, 'id', ImageFixture::png())->jsonSerialize()['cover_key'];
        self::assertSame('image/png', (new GetCoverService($this->equipment, $this->storage))->execute('id')->mimeType);
        $second = $this->upload->execute($this->admin, 'id', ImageFixture::png())->jsonSerialize()['cover_key'];
        self::assertNotSame($first, $second);
        self::assertArrayNotHasKey($first, $this->storage->images);
        self::assertArrayHasKey($second, $this->storage->images);
        $this->remove->execute($this->admin, 'id');
        $version = $this->equipment->rows['id']->version;
        $this->remove->execute($this->admin, 'id');
        self::assertSame($version, $this->equipment->rows['id']->version);
        self::assertNull($this->equipment->rows['id']->equipment->coverKey);
        self::assertSame([], $this->storage->images);
    }

    public function test_technician_cannot_write_even_for_a_missing_equipment(): void
    {
        $actor = new User('user', 'operator', 'fixture-hash', 'lab_technician');
        $this->rejects('FORBIDDEN_ROLE', fn () => $this->upload->execute($actor, 'missing', 'invalid'));
        $this->rejects('FORBIDDEN_ROLE', fn () => $this->remove->execute($actor, 'missing'));
        self::assertSame([], $this->equipment->lookups);
        self::assertSame([], $this->storage->images);
    }

    public function test_invalid_image_does_not_change_equipment_or_store_bytes(): void
    {
        $before = $this->equipment->rows;
        $this->rejects('VALIDATION_ERROR', fn () => $this->upload->execute($this->admin, 'id', '<svg/>'));
        self::assertSame($before, $this->equipment->rows);
        self::assertSame([], $this->storage->images);
    }

    public function test_losing_upload_removes_only_its_new_image(): void
    {
        $first = $this->upload->execute($this->admin, 'id', ImageFixture::png())->jsonSerialize()['cover_key'];
        $winner = $this->storage->put(new CoverImage(ImageFixture::png()));
        $this->equipment->beforeSave = static function ($repository) use ($winner): void {
            $current = $repository->rows['id'];
            $repository->rows['id'] = new StoredEquipment($current->equipment->withCover($winner), '3');
        };
        $this->rejects('EQUIPMENT_NOT_AVAILABLE', fn () => $this->upload->execute($this->admin, 'id', ImageFixture::png()));
        self::assertSame($winner, $this->equipment->rows['id']->equipment->coverKey);
        self::assertSame([$first, $winner], array_keys($this->storage->images));
    }

    public function test_losing_remove_does_not_delete_the_winning_photo(): void
    {
        $old = $this->upload->execute($this->admin, 'id', ImageFixture::png())->jsonSerialize()['cover_key'];
        $this->equipment->beforeSave = static function ($repository): void {
            $current = $repository->rows['id'];
            $repository->rows['id'] = new StoredEquipment($current->equipment->withdraw(), '3');
        };
        $this->rejects('EQUIPMENT_NOT_AVAILABLE', fn () => $this->remove->execute($this->admin, 'id'));
        self::assertArrayHasKey($old, $this->storage->images);
        self::assertSame([], $this->storage->deleted);
    }

    public function test_missing_and_retired_equipment_do_not_expose_photos(): void
    {
        $get = new GetCoverService($this->equipment, $this->storage);
        $this->rejects('NOT_FOUND', fn () => $get->execute('id'));
        $this->rejects('NOT_FOUND', fn () => $this->upload->execute($this->admin, 'missing', ImageFixture::png()));
        $this->upload->execute($this->admin, 'id', ImageFixture::png());
        $current = $this->equipment->rows['id'];
        $this->equipment->save($current->equipment->retire(new \DateTimeImmutable('2026-11-17T00:00:00Z')), $current->version);
        $this->rejects('NOT_FOUND', fn () => $get->execute('id'));
    }

    private function rejects(string $code, callable $operation): void
    {
        try { $operation(); self::fail('Se aceptó una operación inválida.'); }
        catch (DomainViolation $error) { self::assertSame($code, $error->errorCode); }
    }
}
