<?php

declare(strict_types=1);

namespace Tests\Contract;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lab\Application\Ports\Clock;
use Lab\Application\Ports\TokenIssuer;
use Tests\Fakes\FrozenClock;
use Tests\Integration\LoanDatabaseCase;

final class LoanHttpTest extends LoanDatabaseCase
{
    private string $token;
    private function at(string $instant): void
    {
        app()->instance(Clock::class, new FrozenClock(new DateTimeImmutable($instant)));
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['lab.jwt_secret' => bin2hex(random_bytes(32))]);
        $this->at('2047-02-01T23:59:59.999999Z');
        $this->token = app(TokenIssuer::class)->issue($this->actor(), 28800);
        $this->withToken($this->token);
    }

    public function test_full_http_loan_lifecycle_and_inclusive_filters(): void
    {
        $researcher = $this->researcher();
        $equipment = $this->equipment();
        $loan = $this->postJson('/api/v1/loans', ['researcher_id' => $researcher,
            'items' => [['equipment_id' => $equipment]]])
            ->assertCreated()->assertJsonPath('status', 'open')->json();
        $id = $loan['id'];
        $item = $loan['items'][0]['id'];
        $base = '/api/v1/loans?researcher_id=' . $researcher;
        $this->getJson("/api/v1/loans/$id")->assertOk()->assertExactJson($loan);
        foreach (['', '&from=2047-02-01', '&to=2047-02-01', '&from=2047-02-01&to=2047-02-01', '&status=open'] as $filter) {
            $this->getJson($base . $filter)->assertOk()->assertJsonPath('total', 1)->assertJsonPath('items.0.id', $id);
        }
        foreach (['&from=2047-02-02', '&to=2047-01-31', '&status=closed', '&status=overdue'] as $filter) {
            $this->getJson($base . $filter)->assertOk()->assertJsonPath('total', 0)->assertJsonCount(0, 'items');
        }
        $this->getJson($base . '&page=2&size=1')->assertOk()->assertJsonPath('total', 1)->assertJsonCount(0, 'items');
        $this->getJson('/api/v1/loans?researcher_id=' . $this->researcher())->assertOk()->assertJsonPath('total', 0);
        $this->getJson("/api/v1/researchers/$researcher")->assertOk()->assertJsonPath('active_loans', 1);
        DB::table('lab_equipment.equipment')->where('id', $equipment)->update(['name' => 'Nuevo nombre']);
        $this->getJson("/api/v1/loans/$id")->assertOk()->assertJsonPath('items.0.equipment_name', 'Medidor');

        $this->at('2047-02-06T23:59:59.999999Z');
        $this->getJson($base . '&status=open')->assertOk()->assertJsonPath('total', 1);
        $overdue = $this->getJson('/api/v1/loans/overdue')->assertOk()->json('items');
        self::assertNotContains($item, array_column($overdue, 'item_id'));
        $this->at('2047-02-07T00:00:00Z');
        $this->getJson($base . '&status=overdue')->assertOk()->assertJsonPath('total', 1);
        $this->getJson("/api/v1/loans/$id")->assertOk()->assertJsonPath('status', 'overdue');
        $overdue = $this->getJson('/api/v1/loans/overdue')->assertOk()->json('items');
        $line = array_values(array_filter($overdue, fn ($row) => $row['item_id'] === $item));
        self::assertCount(1, $line);
        self::assertSame(0, $line[0]['days_late']);
        self::assertSame('Medidor', $line[0]['equipment_name']);
        $this->postJson('/api/v1/loans', ['researcher_id' => $researcher, 'items' => [['equipment_id' => $this->equipment()]]])
            ->assertStatus(409)->assertJsonPath('code', 'RESEARCHER_HAS_OVERDUE');
        $this->call('POST', "/api/v1/loans/$id/items/$item/return",
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->token], content: '{}')->assertOk()->assertJsonPath('id', $item);
        $this->getJson($base . '&status=closed')->assertOk()->assertJsonPath('total', 1);
        $this->getJson("/api/v1/loans/$id")->assertOk()->assertJsonPath('status', 'closed');
        $this->getJson("/api/v1/researchers/$researcher")->assertOk()->assertJsonPath('active_loans', 0);
        self::assertSame(1, $this->stock($equipment));
        $this->call('POST', "/api/v1/loans/$id/items/$item/return",
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->token], content: '{}')->assertStatus(409);
        $this->deleteJson("/api/v1/loans/$id")->assertStatus(405)->assertJsonPath('code', 'METHOD_NOT_ALLOWED');
    }

    public function test_loan_query_validation_missing_records_and_token_errors(): void
    {
        foreach (['status=bad', 'status[]=open', 'from=2047-02-30', 'to=', 'from=2047-02-02&to=2047-02-01',
            'researcher_id=bad', 'page=0', 'size=51', 'page[]=1', 'extra=1'] as $query) {
            $this->getJson('/api/v1/loans?' . $query)->assertStatus(422)->assertJsonStructure(['code', 'message', 'details']);
        }
        $this->getJson('/api/v1/loans/overdue?page=1')->assertStatus(422);
        $this->getJson('/api/v1/loans/invalid')->assertStatus(422);
        $this->getJson('/api/v1/loans/' . Str::uuid())->assertNotFound()->assertJsonPath('code', 'NOT_FOUND');
        $this->withToken('invalid')->getJson('/api/v1/loans')->assertStatus(401)->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_list_is_sorted_by_time_then_identifier_and_totals_precede_paging(): void
    {
        $researcher = $this->researcher();
        $ids = [];
        foreach ([$this->equipment(), $this->equipment()] as $equipment) {
            $ids[] = $this->postJson('/api/v1/loans', ['researcher_id' => $researcher,
                'items' => [['equipment_id' => $equipment]]])->assertCreated()->json('id');
        }
        sort($ids);
        $base = '/api/v1/loans?researcher_id=' . $researcher . '&size=1';
        $this->getJson($base)->assertOk()->assertJsonPath('total', 2)->assertJsonPath('items.0.id', $ids[0]);
        $this->getJson($base . '&page=2')->assertOk()->assertJsonPath('total', 2)->assertJsonPath('items.0.id', $ids[1]);
        DB::table('lab_equipment.loan')->where('id', $ids[1])->update(['loaned_at' => '2047-02-02T00:00:00Z']);
        DB::table('lab_equipment.loan_item')->where('loan_id', $ids[1])->update(['due_at' => '2047-02-07T00:00:00Z']);
        $this->getJson($base)->assertOk()->assertJsonPath('items.0.id', $ids[1]);
    }
}
