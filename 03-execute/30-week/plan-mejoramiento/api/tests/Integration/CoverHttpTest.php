<?php

declare(strict_types=1);

namespace Tests\Integration;

use Illuminate\Support\Facades\DB;
use Lab\Application\Ports\TokenIssuer;
use Lab\Application\Services\RemoveCoverService;
use Lab\Domain\User;
use Symfony\Component\Process\Process;
use Tests\Fakes\ImageFixture;

final class CoverHttpTest extends LoanDatabaseCase
{
    public function test_real_multipart_put_download_and_removal_with_authentication(): void
    {
        $actor = $this->actor();
        DB::table('lab_equipment.user')->where('id', $actor->id)->update(['role' => 'admin']);
        $admin = new User($actor->id, $actor->username, 'fixture-hash', 'admin');
        $equipment = $this->equipment();
        $secret = bin2hex(random_bytes(32));
        config(['lab.jwt_secret' => $secret]);
        $token = app(TokenIssuer::class)->issue($admin, 28800);
        $server = new Process([PHP_BINARY, '-S', '127.0.0.1:0', 'public/index.php'], base_path(), ['JWT_SECRET' => $secret]);
        $server->setTimeout(30);
        $server->start();
        try {
            $port = null;
            $server->waitUntil(static function ($type, $output) use (&$port) {
                if (preg_match('/127\\.0\\.0\\.1:(\\d+)\\) started/', $output, $match)) {
                    $port = (int) $match[1];
                }
                return $port !== null;
            });
            self::assertNotNull($port);
            $path = "/api/v1/equipment/$equipment/cover";
            [$status] = $this->request($port, 'GET', $path, '');
            self::assertSame(401, $status);
            foreach ([['fake image', 422], [ImageFixture::png() . str_repeat('x', 2097152), 422],
                [ImageFixture::png() . str_repeat('x', 2097152 - strlen(ImageFixture::png())), 200]] as [$bytes, $expected]) {
                $body = "--lab-boundary\r\nContent-Disposition: form-data; name=\"file\"; filename=\"cover.png\"\r\n"
                    . "Content-Type: image/png\r\n\r\n$bytes\r\n--lab-boundary--\r\n";
                [$status, , $response] = $this->request($port, 'PUT', $path, $token, $body,
                    'multipart/form-data; boundary=lab-boundary');
                self::assertSame($expected, $status, $response);
                if ($expected === 422) {
                    self::assertSame('VALIDATION_ERROR', json_decode($response, true, flags: JSON_THROW_ON_ERROR)['code']);
                }
            }
            [$status, $headers, $bytes] = $this->request($port, 'GET', $path, $token);
            self::assertSame(200, $status);
            self::assertStringContainsString('Content-Type: image/png', $headers);
            self::assertSame(2097152, strlen($bytes));
            self::assertStringStartsWith(ImageFixture::png(), $bytes);
            [$status, , $body] = $this->request($port, 'DELETE', $path, $token);
            self::assertSame(204, $status);
            self::assertSame('', $body);
            self::assertNull(DB::table('lab_equipment.equipment')->where('id', $equipment)->value('cover_key'));
            [$status] = $this->request($port, 'GET', $path, $token);
            self::assertSame(404, $status);
        } finally {
            $server->stop(1);
            app(RemoveCoverService::class)->execute($admin, $equipment);
        }
    }

    private function request(int $port, string $method, string $path, string $token, string $body = '', string $type = 'application/json'): array
    {
        $socket = stream_socket_client("tcp://127.0.0.1:$port", timeout: 4);
        self::assertIsResource($socket);
        stream_set_timeout($socket, 10);
        try {
            $request = "$method $path HTTP/1.1\r\nHost: localhost\r\nAuthorization: Bearer $token\r\n"
                . "Connection: close\r\nContent-Type: $type\r\nContent-Length: " . strlen($body) . "\r\n\r\n$body";
            $offset = 0;
            while ($offset < strlen($request)) {
                $written = fwrite($socket, substr($request, $offset));
                self::assertGreaterThan(0, $written);
                $offset += $written;
            }
            [$headers, $response] = explode("\r\n\r\n", stream_get_contents($socket), 2);
            return [(int) substr($headers, 9, 3), $headers, $response];
        } finally {
            fclose($socket);
        }
    }
}
