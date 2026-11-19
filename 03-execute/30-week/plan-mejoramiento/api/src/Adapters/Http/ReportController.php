<?php

declare(strict_types=1);

namespace Lab\Adapters\Http;

use Illuminate\Http\Request;
use Lab\Application\Services\MostBorrowedReportService;

final class ReportController
{
    public function get(Request $request, MostBorrowedReportService $service): mixed
    {
        $q = Input::query($request, ['from', 'to']);
        return response()->json($service->execute($request->attributes->get('actor'),
            Input::text($q['from'] ?? null), Input::text($q['to'] ?? null)));
    }
}
