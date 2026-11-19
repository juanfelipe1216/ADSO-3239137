<?php

declare(strict_types=1);

namespace Lab\Adapters\Http;

use Illuminate\Http\Request;
use Lab\Application\Services\CreateEquipmentService;
use Lab\Application\Services\UpdateEquipmentService;
use Lab\Application\Services\GetEquipmentService;
use Lab\Application\Services\SearchEquipmentService;
use Lab\Application\Services\RetireEquipmentService;
use Lab\Application\Services\ListCategoriesService;

final class EquipmentController
{
    public function categories(Request $request, ListCategoriesService $service): mixed
    {
        Input::query($request);
        return response()->json($service->execute());
    }

    public function search(Request $request, SearchEquipmentService $service): mixed
    {
        $q = Input::query($request, ['q', 'category_id', 'page', 'size']);
        return response()->json($service->execute(Input::text($q['q'] ?? ''),
            isset($q['category_id']) ? Input::uuid($q['category_id']) : null,
            Input::integer($q['page'] ?? 1, true), Input::integer($q['size'] ?? 20, true)));
    }

    public function get(Request $request, string $id, GetEquipmentService $service): mixed
    {
        Input::query($request);
        return response()->json($service->execute(Input::uuid($id)));
    }

    public function create(Request $request, CreateEquipmentService $service): mixed
    {
        $d = Input::body($request, ['name', 'manufacturer', 'reference', 'category_id', 'total_units']);
        return response()->json($service->execute($request->attributes->get('actor'),
            Input::text($d['name']), Input::text($d['manufacturer']), Input::text($d['reference']),
            Input::uuid($d['category_id']), Input::integer($d['total_units'])), 201);
    }

    public function update(Request $request, string $id, UpdateEquipmentService $service): mixed
    {
        $d = Input::body($request, ['name', 'manufacturer', 'category_id', 'total_units']);
        return response()->json($service->execute($request->attributes->get('actor'), Input::uuid($id),
            Input::text($d['name']), Input::text($d['manufacturer']), Input::uuid($d['category_id']), Input::integer($d['total_units'])));
    }

    public function retire(Request $request, string $id, RetireEquipmentService $service): mixed
    {
        Input::query($request);
        $service->execute($request->attributes->get('actor'), Input::uuid($id));
        return response()->noContent();
    }
}
