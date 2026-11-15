<?php

namespace Tests\Contract;

use Tests\TestCase;

final class HealthContractTest extends TestCase
{
    public function test_health_is_public_and_has_the_exact_contract(): void
    {
        $this->getJson('/api/v1/health')->assertOk()->assertExactJson(['status' => 'ok']);
    }

    public function test_framework_health_alias_is_not_exposed(): void
    {
        $this->getJson('/up')->assertNotFound()->assertExactJson([
            'code' => 'NOT_FOUND', 'message' => 'El recurso solicitado no existe.', 'details' => [],
        ]);
    }

    public function test_health_rejects_a_write_operation_with_a_contract_error(): void
    {
        $this->postJson('/api/v1/health')->assertStatus(405)->assertJsonPath('code', 'METHOD_NOT_ALLOWED');
    }
}
