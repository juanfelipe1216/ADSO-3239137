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
            $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;
            [$code, $message] = match ($status) {
                404 => ['NOT_FOUND', 'El recurso solicitado no existe.'],
                405 => ['METHOD_NOT_ALLOWED', 'El método solicitado no está permitido.'],
                default => ['INTERNAL_ERROR', 'No se pudo completar la solicitud.'],
            };
            return response()->json(['code' => $code, 'message' => $message, 'details' => []], $status);
        });
    })->create();
