<?php

declare(strict_types=1);

namespace Lab\Adapters\Http;

use Closure;
use Illuminate\Http\Request;
use Lab\Application\Ports\TokenVerifier;
use Lab\Domain\DomainViolation;
use Lab\Domain\UserRole;

final readonly class Access
{
    public function __construct(private TokenVerifier $tokens) {}

    public function handle(Request $request, Closure $next, string $role = ''): mixed
    {
        $actor = $this->tokens->verify($request->bearerToken() ?? '');
        if ($role === 'admin' && $actor->role !== UserRole::Admin) {
            throw new DomainViolation('FORBIDDEN_ROLE', 'Esta operación requiere el rol de administrador.');
        }
        $request->attributes->set('actor', $actor);
        return $next($request);
    }
}
