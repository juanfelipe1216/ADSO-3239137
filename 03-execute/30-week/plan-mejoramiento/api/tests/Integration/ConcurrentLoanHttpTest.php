<?php

declare(strict_types=1);

namespace Tests\Integration;

use Illuminate\Support\Facades\DB;
use Lab\Application\DTOs\AuthenticatedIdentity;
use Lab\Application\Ports\LoanRepository;
use Lab\Application\Ports\TokenIssuer;
use Lab\Application\Services\LendEquipmentService;
use Lab\Domain\User;
use Symfony\Component\Process\Process;

/** Two independent HTTP servers share PostgreSQL. The parent releases its gate only after both sessions wait. */
final class ConcurrentLoanHttpTest extends LoanDatabaseCase
{
    private array $servers = [];
    private array $clients = [];

    protected function tearDown(): void
    {
        foreach ($this->clients as $client) {
            fclose($client);
        }
        foreach ($this->servers as $server) {
            $server->stop(1);
        }
        parent::tearDown();
    }

    private function race(array $requests, callable $gate): array
    {
        $secret = bin2hex(random_bytes(32));
        config(['lab.jwt_secret' => $secret]);
        $tag = 'loan-test-' . bin2hex(random_bytes(8));
        DB::beginTransaction();
        try {
            $gate();
            foreach ($requests as [$actor, $path, $payload]) {
                $server = new Process([PHP_BINARY, '-S', '127.0.0.1:0', 'public/index.php'], base_path(),
                    ['JWT_SECRET' => $secret, 'PGAPPNAME' => $tag]);
                $this->servers[] = $server;
                $server->setTimeout(20);
                $server->start();
                $port = null;
                $server->waitUntil(static function ($type, $output) use (&$port) {
                    if (preg_match('/127\.0\.0\.1:(\d+)\) started/', $output, $match)) {
                        $port = (int) $match[1];
                    }
                    return $port !== null;
                });
                self::assertNotNull($port);
                $socket = stream_socket_client("tcp://127.0.0.1:$port", timeout: 4);
                self::assertIsResource($socket);
                $this->clients[] = $socket;
                stream_set_timeout($socket, 8);
                $token = app(TokenIssuer::class)->issue($actor, 28800);
                $body = $payload === null ? '' : json_encode($payload, JSON_THROW_ON_ERROR);
                fwrite($socket, "POST /api/v1/$path HTTP/1.1\r\nHost: localhost\r\nAuthorization: Bearer $token\r\n"
                    . "Connection: close\r\nContent-Type: application/json\r\nContent-Length: " . strlen($body) . "\r\n\r\n" . $body);
            }
            $deadline = microtime(true) + 8;
            do {
                DB::selectOne('SELECT pg_stat_clear_snapshot()');
                $waiting = (int) DB::selectOne("SELECT count(*) AS n FROM pg_stat_activity
                    WHERE application_name = ? AND cardinality(pg_blocking_pids(pid)) > 0", [$tag])->n;
                if ($waiting === 2) {
                    break;
                }
                usleep(10000);
            } while (microtime(true) < $deadline);
            self::assertSame(2, $waiting, 'Both request sessions must overlap at a database lock.');
        } finally {
            DB::rollBack();
        }
        $responses = [];
        foreach ($this->clients as $socket) {
            [$headers, $body] = explode("\r\n\r\n", stream_get_contents($socket), 2);
            self::assertMatchesRegularExpression('/^HTTP\/1\.[01] \d{3}/', $headers);
            $responses[] = [(int) substr($headers, 9, 3), json_decode($body, true, flags: JSON_THROW_ON_ERROR)];
        }
        usort($responses, fn ($a, $b) => $a[0] <=> $b[0]);
        return $responses;
    }

    private function lendRequest(User $actor, string $researcher, string $equipment): array
    {
        return [$actor, 'loans', ['researcher_id' => $researcher, 'items' => [['equipment_id' => $equipment]]]];
    }

    private function seedLoan(User $actor, string $researcher, string $equipment): array
    {
        return app(LendEquipmentService::class)->execute(new AuthenticatedIdentity($actor->id, $actor->role),
            $researcher, [$equipment])->jsonSerialize();
    }

    private function lockEquipment(string $equipment): void
    {
        DB::table('lab_equipment.equipment')->where('id', $equipment)->lockForUpdate()->first();
    }

    private function lockResearcher(string $researcher): void
    {
        DB::selectOne('SELECT pg_advisory_xact_lock(hashtextextended(CAST(? AS uuid)::text, 0))', [$researcher]);
    }

    public function test_two_operators_disputing_last_unit_get_201_and_409(): void
    {
        $equipment = $this->equipment();
        $r1 = $this->researcher();
        $r2 = $this->researcher();
        $requests = [$this->lendRequest($this->actor(), $r1, $equipment), $this->lendRequest($this->actor(), $r2, $equipment)];
        [$won, $lost] = $this->race($requests, fn () => $this->lockEquipment($equipment));
        self::assertSame([201, 409], [$won[0], $lost[0]]);
        self::assertSame('EQUIPMENT_NOT_AVAILABLE', $lost[1]['code']);
        self::assertSame(0, $this->stock($equipment));
        self::assertSame(1, DB::table('lab_equipment.loan')->whereIn('researcher_id', [$r1, $r2])->count());
        self::assertSame(1, DB::table('lab_equipment.loan_item')->where('equipment_id', $equipment)->count());
    }

    public function test_double_return_gets_200_and_409_with_one_increment(): void
    {
        $equipment = $this->equipment();
        $researcher = $this->researcher();
        $actor = $this->actor();
        $loan = $this->seedLoan($actor, $researcher, $equipment);
        $item = $loan['items'][0]['id'];
        $path = 'loans/' . $loan['id'] . "/items/$item/return";
        [$won, $lost] = $this->race([[$actor, $path, null], [$this->actor(), $path, null]], fn () => $this->lockResearcher($researcher));
        self::assertSame([200, 409], [$won[0], $lost[0]]);
        self::assertSame('ITEM_ALREADY_RETURNED', $lost[1]['code']);
        self::assertSame($item, $won[1]['id']);
        self::assertSame(1, $this->stock($equipment));
        $stored = app(LoanRepository::class)->findById($loan['id']);
        self::assertSame($won[1]['returned_at'], $stored->items[0]->returnedAt->format('Y-m-d\TH:i:s\Z'));
        self::assertSame([], app(LoanRepository::class)->pendingItemsForResearcher($researcher));
    }

    public function test_two_requests_for_same_researcher_cannot_exceed_two_pending_lines(): void
    {
        $actor = $this->actor();
        $researcher = $this->researcher();
        $this->seedLoan($actor, $researcher, $this->equipment());
        $a = $this->equipment();
        $b = $this->equipment();
        [$won, $lost] = $this->race([$this->lendRequest($actor, $researcher, $a), $this->lendRequest($this->actor(), $researcher, $b)],
            fn () => $this->lockResearcher($researcher));
        self::assertSame([201, 422], [$won[0], $lost[0]]);
        self::assertSame('RESEARCHER_LIMIT_EXCEEDED', $lost[1]['code']);
        self::assertSame('active_loans', $lost[1]['details'][0]['field']);
        self::assertCount(2, app(LoanRepository::class)->pendingItemsForResearcher($researcher));
        self::assertSame(1, $this->stock($a) + $this->stock($b));
    }
}
