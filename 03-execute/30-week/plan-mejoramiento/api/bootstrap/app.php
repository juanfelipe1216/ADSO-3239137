<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(api: __DIR__.'/../routes/api.php', apiPrefix: 'api/v1')
    ->withMiddleware(function (Middleware $middleware): void {})
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $exception, Request $request) {
            if ($exception instanceof \Lab\Domain\DomainViolation) {
                $status = match ($exception->errorCode) {
                    'UNAUTHENTICATED' => 401,
                    'NOT_FOUND' => 404,
                    'EQUIPMENT_NOT_AVAILABLE', 'ITEM_ALREADY_RETURNED', 'RESEARCHER_HAS_OVERDUE' => 409,
                    'VALIDATION_ERROR', 'RESEARCHER_LIMIT_EXCEEDED', 'DUPLICATE_EQUIPMENT_IN_LOAN' => 422,
                    default => null,
                };
                if ($status !== null) {
                    $details = isset($exception->context['active_loans'])
                        ? [['field' => 'active_loans', 'issue' => 'Equipos pendientes: ' . $exception->context['active_loans']]]
                        : [];
                    return response()->json(['code' => $exception->errorCode, 'message' => $exception->getMessage(), 'details' => $details], $status);
                }
            }
            $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;
            [$code, $message] = match ($status) {
                404 => ['NOT_FOUND', 'El recurso solicitado no existe.'],
                405 => ['METHOD_NOT_ALLOWED', 'El método solicitado no está permitido.'],
                default => ['INTERNAL_ERROR', 'No se pudo completar la solicitud.'],
            };
            return response()->json(['code' => $code, 'message' => $message, 'details' => []], $status);
        });
    })->create();
