<?php

declare(strict_types=1);

namespace Tests\Integration;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Lab\Application\Ports\Clock;
use Lab\Application\Ports\LoanRepository;
use Lab\Application\Ports\OverdueReader;
use Lab\Application\Ports\TransactionManager;
use Lab\Application\Services\LendEquipmentService;
use Lab\Application\Services\ReturnItemService;
use Tests\Fakes\FrozenClock;

final class LoanPersistenceTest extends LoanDatabaseCase
{
    public function test_round_trip_and_transaction_rollback(): void
    {
        $user = $this->actor();
        $actor = new \Lab\Application\DTOs\AuthenticatedIdentity($user->id, $user->role);
        $researcher = $this->researcher();
        $equipment = $this->equipment();
        $at = new DateTimeImmutable('2026-11-18T15:00:00.123456Z');
        app()->instance(Clock::class, new FrozenClock($at));
        $lend = app(LendEquipmentService::class);
        $loans = app(LoanRepository::class);
        $failure = new \RuntimeException('Fixture failure after writes');
        try {
            app(TransactionManager::class)->forResearcher($researcher, function () use ($lend, $actor, $researcher, $equipment, $failure) {
                $lend->execute($actor, $researcher, [$equipment]);
                self::assertSame(0, $this->stock($equipment));
                throw $failure;
            });
            self::fail('Expected rollback');
        } catch (\RuntimeException $error) {
            self::assertSame($failure, $error);
        }
        self::assertSame(1, $this->stock($equipment));
        self::assertSame([], $loans->pendingItemsForResearcher($researcher));
        self::assertFalse(DB::table('lab_equipment.loan')->where('researcher_id', $researcher)->exists());
        $result = $lend->execute($actor, $researcher, [$equipment])->jsonSerialize();
        $loan = $loans->findById($result['id']);
        self::assertEquals($at, $loan->loanedAt);
        self::assertSame($actor->userId, $loan->loanedByUserId);
        $item = $loan->items[0];
        self::assertSame('medición', $item->categoryName);
        $reader = app(OverdueReader::class);
        $ids = fn ($time) => array_map(fn ($row) => $row->item->id, $reader->findOverdue($time));
        self::assertNotContains($item->id, $ids($item->dueAt));
        self::assertContains($item->id, $ids($item->dueAt->modify('+1 microsecond')));
        app(ReturnItemService::class)->execute($loan->id, $item->id);
        self::assertNotContains($item->id, $ids($item->dueAt->modify('+1 day')));
        self::assertSame(1, $this->stock($equipment));
        self::assertEquals($at, $loans->findById($loan->id)->items[0]->returnedAt);
    }
}
