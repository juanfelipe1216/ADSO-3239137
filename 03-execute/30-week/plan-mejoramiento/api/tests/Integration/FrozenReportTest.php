<?php

declare(strict_types=1);

namespace Tests\Integration;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lab\Application\DTOs\MostBorrowedRow;
use Lab\Application\Ports\EquipmentRepository;
use Lab\Application\Ports\ReportReader;
use Lab\Application\Ports\ResearcherRepository;
use Lab\Application\Ports\UserRepository;
use Lab\Application\Services\MostBorrowedReportService;
use Lab\Domain\Equipment;
use Lab\Domain\Loan;
use Lab\Domain\LoanItem;
use Lab\Domain\Researcher;
use Lab\Domain\User;
use Lab\Domain\ValueObjects\DateRange;
use Lab\Domain\ValueObjects\DocumentNumber;
use Lab\Domain\ValueObjects\Quantity;
use Lab\Domain\ValueObjects\Reference;
use Tests\Fakes\InMemoryLoanRepository;
use Tests\Fakes\InMemoryReportReader;
use Tests\TestCase;

final class FrozenReportTest extends TestCase
{
    private InMemoryLoanRepository $fakeLoans;
    private InMemoryReportReader $fakeReader;
    private ReportReader $realReader;
    private EquipmentRepository $equipment;
    private User $admin;
    private string $researcherId;

    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        self::assertFalse(DB::table('lab_equipment.loan')->where('loaned_at', '>=', '2042-01-01T00:00:00Z')
            ->where('loaned_at', '<', '2042-02-01T00:00:00Z')->exists(), 'El intervalo de la prueba debe estar vacío; no se borran datos existentes.');
        $this->fakeLoans = new InMemoryLoanRepository();
        $this->fakeReader = new InMemoryReportReader($this->fakeLoans);
        $this->realReader = $this->app->make(ReportReader::class);
        $this->equipment = $this->app->make(EquipmentRepository::class);
        $this->admin = new User((string) Str::uuid(), 'report-' . bin2hex(random_bytes(8)), 'fixture-hash', 'admin');
        $this->app->make(UserRepository::class)->add($this->admin);
        $this->researcherId = (string) Str::uuid();
        $this->app->make(ResearcherRepository::class)->add(new Researcher($this->researcherId, 'Identidad privada de prueba', new DocumentNumber(bin2hex(random_bytes(8)))));
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    private function item(string $name = 'Medidor'): Equipment
    {
        $id = (string) Str::uuid();
        $equipment = Equipment::create($id, $name, 'Fabricante privado', new Reference($id),
            '11111111-1111-4111-8111-111111111111', 'medición', new Quantity(20));
        $this->equipment->add($equipment);
        return $equipment;
    }

    /** Seed only test data until the production loan adapter is implemented in T-13. */
    private function record(array $equipment, string $at, bool $returned = true): Loan
    {
        $instant = new DateTimeImmutable($at);
        $items = [];
        foreach ($equipment as $item) {
            $line = LoanItem::borrow((string) Str::uuid(), $item, $instant);
            if ($returned) {
                $line = $line->returnAt($instant->modify('+1 day'));
            } else {
                $stored = $this->equipment->findActive($item->id);
                $this->equipment->save($stored->equipment->withdraw(), $stored->version);
            }
            $items[] = $line;
        }
        $loan = new Loan((string) Str::uuid(), $this->researcherId, $this->admin->id, $instant, $items);
        $this->fakeLoans->add($loan);
        DB::table('lab_equipment.loan')->insert(['id' => $loan->id, 'researcher_id' => $loan->researcherId,
            'loaned_by_user_id' => $loan->loanedByUserId, 'loaned_at' => $loan->loanedAt->format('Y-m-d H:i:s.uP')]);
        foreach ($items as $line) {
            DB::table('lab_equipment.loan_item')->insert(['id' => $line->id, 'loan_id' => $loan->id,
                'equipment_id' => $line->equipmentId, 'equipment_name' => $line->equipmentName,
                'reference' => $line->reference->value, 'category_name' => $line->categoryName,
                'due_at' => $line->dueAt->format('Y-m-d H:i:s.uP'), 'returned_at' => $line->returnedAt?->format('Y-m-d H:i:s.uP')]);
        }
        return $loan;
    }

    private function assertBoth(DateRange $range, array $expected): void
    {
        foreach ([$this->fakeReader, $this->realReader] as $reader) {
            $actual = array_map(static fn (MostBorrowedRow $row): array => $row->jsonSerialize(), $reader->mostBorrowed($range));
            self::assertSame($expected, $actual);
        }
    }

    private function row(Equipment $equipment, int $count): array
    {
        return ['equipment_id' => $equipment->id, 'equipment_name' => $equipment->name,
            'category_name' => $equipment->categoryName, 'times_borrowed' => $count];
    }

    public function test_fake_and_postgres_share_empty_boundaries_counts_order_and_privacy(): void
    {
        $range = new DateRange('2042-01-10', '2042-01-11');
        $this->assertBoth($range, []);
        $popular = $this->item('Último');
        $first = $this->item('10');
        $second = $this->item('10');
        $last = $this->item('2');
        $this->record([$popular], '2042-01-09T23:59:59.999999Z');
        $this->record([$popular], '2042-01-09T19:00:00-05:00');
        $this->record([$popular], '2042-01-11T23:59:59.999999Z', false);
        $this->record([$popular], '2042-01-12T00:00:00Z');
        $this->record([$second, $first], '2042-01-10T12:00:00Z');
        $this->record([$last], '2042-01-10T13:00:00Z');
        $twins = [$first, $second];
        usort($twins, static fn (Equipment $a, Equipment $b): int => strcmp($a->id, $b->id));
        $expected = [$this->row($popular, 2), $this->row($twins[0], 1), $this->row($twins[1], 1), $this->row($last, 1)];
        $this->assertBoth($range, $expected);
        $result = (new MostBorrowedReportService($this->realReader))->execute($this->admin, '2042-01-10', '2042-01-11');
        self::assertSame(['from' => '2042-01-10', 'to' => '2042-01-11', 'rows' => $expected], $result);
        self::assertStringNotContainsString($this->researcherId, json_encode($result, JSON_THROW_ON_ERROR));
        self::assertStringNotContainsString($this->admin->id, json_encode($result, JSON_THROW_ON_ERROR));
        $this->assertBoth(new DateRange('2042-01-20', '2042-01-20'), []);
    }

    public function test_actual_catalog_rename_recategory_return_and_retirement_preserve_frozen_report(): void
    {
        $original = $this->item();
        $oldLoan = $this->record([$original], '2042-01-10T00:00:00Z', false);
        $oldRange = new DateRange('2042-01-10', '2042-01-10');
        $this->assertBoth($oldRange, [$this->row($original, 1)]);
        $stored = $this->equipment->findActive($original->id);
        $renamed = $stored->equipment->revise('Osciloscopio', 'Otro fabricante',
            '22222222-2222-4222-8222-222222222222', 'óptica', new Quantity(20));
        $this->equipment->save($renamed, $stored->version);
        $this->record([$renamed], '2042-01-11T00:00:00Z');
        $stored = $this->equipment->findActive($original->id);
        $recategorized = $stored->equipment->revise('Osciloscopio', 'Otro fabricante',
            '33333333-3333-4333-8333-333333333333', 'electrónica', new Quantity(20));
        $this->equipment->save($recategorized, $stored->version);
        $this->record([$recategorized], '2042-01-11T12:00:00Z');
        $range = new DateRange('2042-01-10', '2042-01-11');
        $expected = [$this->row($original, 1), $this->row($recategorized, 1), $this->row($renamed, 1)];
        $this->assertBoth($range, $expected);
        $returnedAt = new DateTimeImmutable('2042-01-20T00:00:00Z');
        DB::table('lab_equipment.loan_item')->where('id', $oldLoan->items[0]->id)->update(['returned_at' => $returnedAt->format(DATE_ATOM)]);
        $this->fakeLoans->markReturned($oldLoan->id, $oldLoan->items[0]->id, $returnedAt);
        $stored = $this->equipment->findActive($original->id);
        $this->equipment->save($stored->equipment->restock()->retire($returnedAt), $stored->version);
        self::assertNull($this->equipment->findActive($original->id));
        self::assertNotNull(DB::table('lab_equipment.equipment')->where('id', $original->id)->value('deleted_at'));
        $this->assertBoth($oldRange, [$this->row($original, 1)]);
        $this->assertBoth($range, $expected);
        self::assertSame(3, DB::table('lab_equipment.loan_item')->where('equipment_id', $original->id)->count());
    }
}
