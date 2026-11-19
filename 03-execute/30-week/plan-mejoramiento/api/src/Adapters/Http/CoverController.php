<?php

declare(strict_types=1);

namespace Lab\Adapters\Http;

use Illuminate\Http\Request;
use Lab\Application\Services\UploadCoverService;
use Lab\Application\Services\RemoveCoverService;
use Lab\Application\Services\GetCoverService;

final class CoverController
{
    public function upload(Request $request, string $id, UploadCoverService $service): mixed
    {
        if ($request->files->count() === 0 && str_starts_with($request->header('Content-Type', ''), 'multipart/form-data')) {
            try {
                [$fields, $files] = request_parse_body();
                $request->request->replace($fields);
                $request->files->replace($files);
            } catch (\RequestParseBodyException) {
                Input::invalid();
            }
        }
        $file = $request->file('file');
        if ($request->query() !== [] || $request->request->count() !== 0
            || array_keys($request->allFiles()) !== ['file']
            || !$file instanceof \Illuminate\Http\UploadedFile || !$file->isValid()
            || $file->getSize() > 2097152) {
            Input::invalid();
        }
        return response()->json($service->execute($request->attributes->get('actor'), Input::uuid($id), $file->getContent()));
    }

    public function remove(Request $request, string $id, RemoveCoverService $service): mixed
    {
        Input::query($request);
        $service->execute($request->attributes->get('actor'), Input::uuid($id));
        return response()->noContent();
    }

    public function get(Request $request, string $id, GetCoverService $service): mixed
    {
        Input::query($request);
        $image = $service->execute(Input::uuid($id));
        return response($image->bytes, 200, ['Content-Type' => $image->mimeType, 'Cache-Control' => 'private, no-store']);
    }
}
