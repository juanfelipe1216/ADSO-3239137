<?php

declare(strict_types=1);

namespace Lab\Adapters\Http;

use Illuminate\Http\Request;
use Lab\Application\Services\CreateResearcherService;
use Lab\Application\Services\GetResearcherService;
use Lab\Application\Services\SearchResearchersService;

final class ResearcherController
{
    public function create(Request $request, CreateResearcherService $service): mixed
    {
        $d = Input::body($request, ['full_name', 'document_number']);
        $result = $service->execute(Input::text($d['full_name']), Input::text($d['document_number']));
        return response()->json(['id' => $result->id, 'full_name' => $result->fullName, 'document_number' => $result->documentNumber->value], 201);
    }

    public function get(Request $request, string $id, GetResearcherService $service): mixed
    {
        Input::query($request);
        return response()->json($service->execute(Input::uuid($id)));
    }

    public function search(Request $request, SearchResearchersService $service): mixed
    {
        $q = Input::query($request, ['q', 'page', 'size']);
        return response()->json($service->execute(Input::text($q['q'] ?? ''),
            Input::integer($q['page'] ?? 1, true), Input::integer($q['size'] ?? 20, true)));
    }
}
