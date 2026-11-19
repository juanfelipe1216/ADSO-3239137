<?php

declare(strict_types=1);

namespace Tests\Contract;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Lab\Application\Ports\CategoryReader;
use Tests\TestCase;

final class HttpBoundaryTest extends TestCase
{
    public function test_exactly_twenty_one_business_operations(): void
    {
        $actual = [];
        foreach (Route::getRoutes() as $route) {
            foreach (array_diff($route->methods(), ['HEAD', 'OPTIONS']) as $method) {
                $actual[] = $method . ' /' . $route->uri();
            }
        }
        $expected = ['GET /api/v1/health', 'POST /api/v1/auth/login', 'POST /api/v1/users',
            'GET /api/v1/categories', 'GET /api/v1/equipment', 'POST /api/v1/equipment',
            'GET /api/v1/equipment/{id}', 'PUT /api/v1/equipment/{id}', 'DELETE /api/v1/equipment/{id}',
            'GET /api/v1/equipment/{id}/cover', 'PUT /api/v1/equipment/{id}/cover', 'DELETE /api/v1/equipment/{id}/cover',
            'GET /api/v1/researchers', 'POST /api/v1/researchers', 'GET /api/v1/researchers/{id}',
            'GET /api/v1/loans', 'POST /api/v1/loans', 'GET /api/v1/loans/{id}', 'GET /api/v1/loans/overdue',
            'POST /api/v1/loans/{loanId}/items/{itemId}/return', 'GET /api/v1/reports/most-borrowed'];
        sort($expected);
        sort($actual);
        self::assertSame($expected, $actual);
    }

    private function token(int $issuedAt): string
    {
        $secret = bin2hex(random_bytes(32));
        config(['lab.jwt_secret' => $secret]);
        return JWT::encode(['sub' => (string) Str::uuid(), 'role' => 'admin', 'iat' => $issuedAt,
            'exp' => $issuedAt + 28800], $secret, 'HS256');
    }

    public function test_expired_token_is_rejected_by_http(): void
    {
        $this->withToken($this->token(time() - 28800))->getJson('/api/v1/categories')
            ->assertStatus(401)->assertExactJson(['code' => 'UNAUTHENTICATED',
                'message' => 'La sesión no es válida o ha vencido.', 'details' => []]);
    }

    public function test_unexpected_exception_is_sanitized_in_response_and_log(): void
    {
        $logger = $this->createMock(\Illuminate\Log\LogManager::class);
        $logger->expects(self::once())->method('error')->with('Fallo interno de la aplicación.', ['type' => \RuntimeException::class]);
        Log::swap($logger);
        $this->withToken($this->token(time()));
        $marker = 'synthetic-sensitive-' . bin2hex(random_bytes(8));
        $reader = $this->createMock(CategoryReader::class);
        $reader->method('all')->willThrowException(new \RuntimeException($marker));
        app()->instance(CategoryReader::class, $reader);
        $response = $this->getJson('/api/v1/categories')->assertStatus(500)->assertExactJson([
            'code' => 'INTERNAL_ERROR', 'message' => 'No se pudo completar la solicitud.', 'details' => []]);
        self::assertStringNotContainsString($marker, $response->getContent());
    }

    public function test_transport_limit_uses_uniform_json(): void
    {
        $this->call('POST', '/api/v1/auth/login', server: ['CONTENT_LENGTH' => '1073741824'])
            ->assertStatus(413)->assertExactJson(['code' => 'PAYLOAD_TOO_LARGE',
                'message' => 'La solicitud supera el tamaño permitido.', 'details' => []]);
    }

    public function test_cors_allows_only_the_configured_origin(): void
    {
        config(['cors.allowed_origins' => ['http://127.0.0.1:5173']]);
        $this->call('OPTIONS', '/api/v1/equipment', server: ['HTTP_ORIGIN' => 'http://127.0.0.1:5173',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST', 'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'Authorization,Content-Type'])
            ->assertNoContent()->assertHeader('Access-Control-Allow-Origin', 'http://127.0.0.1:5173');
        $response = $this->call('OPTIONS', '/api/v1/equipment', server: ['HTTP_ORIGIN' => 'https://untrusted.example',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST']);
        self::assertNotSame('https://untrusted.example', $response->headers->get('Access-Control-Allow-Origin'));
        self::assertNotSame('*', $response->headers->get('Access-Control-Allow-Origin'));
        $this->getJson('/api/v1/health')->assertOk()->assertExactJson(['status' => 'ok']);
    }
}
