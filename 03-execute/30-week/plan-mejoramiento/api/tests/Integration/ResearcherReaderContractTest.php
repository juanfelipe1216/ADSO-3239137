<?php

declare(strict_types=1);

namespace Tests\Integration;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lab\Application\DTOs\Pagination;
use Lab\Application\Ports\ResearcherReader;
use Lab\Application\Ports\ResearcherRepository;
use Lab\Domain\Researcher;
use Lab\Domain\ValueObjects\DocumentNumber;
use Tests\Fakes\InMemoryResearcherReader;
use Tests\TestCase;

final class ResearcherReaderContractTest extends TestCase
{
    protected function setUp(): void { parent::setUp(); DB::beginTransaction(); }
    protected function tearDown(): void { DB::rollBack(); parent::tearDown(); }

    public function test_real_search_and_detail_match_fake_including_pending_line_count(): void
    {
        $marker = 'r-' . bin2hex(random_bytes(6));
        $researchers = [];
        foreach (['Ana', 'Ana', 'Ángela_100%!'] as $index => $name) {
            $researcher = new Researcher((string) Str::uuid(), $marker . ' ' . $name, new DocumentNumber($marker . $index));
            $this->app->make(ResearcherRepository::class)->add($researcher);
            $researchers[] = $researcher;
        }
        $userId = (string) Str::uuid();
        DB::table('lab_equipment.user')->insert(['id' => $userId, 'username' => $marker, 'password_hash' => 'fixture-hash', 'role' => 'admin']);
        $lines = [];
        for ($loanIndex = 0; $loanIndex < 2; $loanIndex++) {
            $loanId = (string) Str::uuid();
            DB::table('lab_equipment.loan')->insert(['id' => $loanId, 'researcher_id' => $researchers[0]->id,
                'loaned_by_user_id' => $userId, 'loaned_at' => '2026-11-01T00:00:00Z']);
            for ($itemIndex = 0; $itemIndex < 2; $itemIndex++) {
                $equipmentId = (string) Str::uuid();
                DB::table('lab_equipment.equipment')->insert(['id' => $equipmentId, 'name' => 'Equipo', 'manufacturer' => 'Fabricante',
                    'reference' => strtoupper($equipmentId), 'category_id' => '11111111-1111-4111-8111-111111111111',
                    'total_units' => 1, 'available_units' => 0, 'cover_key' => null, 'deleted_at' => null]);
                $returned = $loanIndex === 1 ? '2026-11-05T00:00:00Z' : null;
                DB::table('lab_equipment.loan_item')->insert(['id' => (string) Str::uuid(), 'loan_id' => $loanId,
                    'equipment_id' => $equipmentId, 'equipment_name' => 'Equipo', 'reference' => strtoupper($equipmentId),
                    'category_name' => 'medición', 'due_at' => '2026-11-06T00:00:00Z', 'returned_at' => $returned]);
                $lines[] = ['researcher_id' => $researchers[0]->id, 'loan_id' => $loanId, 'returned_at' => $returned];
            }
        }
        $fake = new InMemoryResearcherReader($researchers, $lines);
        $real = $this->app->make(ResearcherReader::class);
        foreach ([[$marker, 1], [$marker, 2], [$marker, 5], [$marker . ' ÁN', 1], [$marker . ' Ángela_', 1],
            [$marker . ' Ángela_100%!', 1], [$marker . '2', 1], ["' OR 1=1 --", 1]] as [$q, $page]) {
            $pagination = new Pagination($page, 1);
            self::assertSame($fake->search($q, $pagination)->jsonSerialize(), $real->search($q, $pagination)->jsonSerialize());
        }
        foreach ($researchers as $researcher) {
            self::assertSame($fake->findById($researcher->id)->jsonSerialize(), $real->findById($researcher->id)->jsonSerialize());
        }
        self::assertSame(2, $real->findById($researchers[0]->id)->activeLoans);
        self::assertNull($real->findById((string) Str::uuid()));
    }
}
