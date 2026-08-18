<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Secure file serving (§29): business files live on configurable disks (possibly a
 * network share / outside webroot) and are served ONLY through this authenticated
 * controller — never via a public URL or hard-coded absolute path.
 */
class FileController extends Controller
{
    public function show(Request $request, string $category, string $path): StreamedResponse
    {
        abort_unless(in_array($category, config('eams.storage_categories', []), true), 404);
        abort_if(str_contains($path, '..'), 404); // path-traversal guard
        abort_unless(Storage::disk($category)->exists($path), 404);

        return Storage::disk($category)->response($path);
    }
}
