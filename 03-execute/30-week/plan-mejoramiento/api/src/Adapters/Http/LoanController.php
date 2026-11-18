<?php

declare(strict_types=1);

namespace Lab\Adapters\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Lab\Application\Ports\TokenVerifier;
use Lab\Application\Services\LendEquipmentService;
use Lab\Application\Services\ReturnItemService;
use Lab\Domain\DomainViolation;

final class LoanController
{
    private function uuid(mixed $value): string
    {
        if (!is_string($value) || !Str::isUuid($value, 4)) {
            throw new DomainViolation('VALIDATION_ERROR', 'El identificador debe ser un UUID v4.');
        }
        return strtolower($value);
    }

    public function lend(Request $request, TokenVerifier $tokens, LendEquipmentService $service): JsonResponse
    {
        $actor = $tokens->verify($request->bearerToken() ?? '');
        $body = json_decode($request->getContent());
        if (!$body instanceof \stdClass || count(get_object_vars($body)) !== 2 || !is_array($body->items ?? null)
            || count($body->items) < 1 || count($body->items) > 2 || $request->query() !== []) {
            throw new DomainViolation('VALIDATION_ERROR', 'Indica el investigador y uno o dos equipos.');
        }
        $researcher = $this->uuid($body->researcher_id ?? null);
        $ids = [];
        foreach ($body->items as $line) {
            if (!$line instanceof \stdClass || array_keys(get_object_vars($line)) !== ['equipment_id']) {
                throw new DomainViolation('VALIDATION_ERROR', 'Cada línea debe indicar únicamente el equipo.');
            }
            $ids[] = $this->uuid($line->equipment_id);
        }
        return response()->json($service->execute($actor, $researcher, $ids), 201);
    }

    public function returnItem(Request $request, string $loanId, string $itemId, TokenVerifier $tokens, ReturnItemService $service): JsonResponse
    {
        $tokens->verify($request->bearerToken() ?? '');
        $raw = trim($request->getContent());
        $body = json_decode($raw);
        if (($raw !== '' && !($body instanceof \stdClass && get_object_vars($body) === [])) || $request->query() !== []) {
            throw new DomainViolation('VALIDATION_ERROR', 'La devolución no admite datos adicionales.');
        }
        return response()->json($service->execute($this->uuid($loanId), $this->uuid($itemId)));
    }
}
