<?php

declare(strict_types=1);

namespace Lab\Adapters\Http;

use Illuminate\Http\Request;
use Lab\Application\Services\LoginService;
use Lab\Application\Services\CreateLabTechnicianService;

final class AuthController
{
    public function login(Request $request, LoginService $service): mixed
    {
        $data = Input::body($request, ['username', 'password']);
        return response()->json($service->execute(Input::text($data['username']), Input::text($data['password'])));
    }

    public function create(Request $request, CreateLabTechnicianService $service): mixed
    {
        $data = Input::body($request, ['username', 'password', 'role']);
        return response()->json($service->execute($request->attributes->get('actor'),
            Input::text($data['username']), Input::text($data['password']), Input::text($data['role'])), 201);
    }
}
