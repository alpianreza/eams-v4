<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Business-file storage (Q-022): one place to put/delete files on the configurable
 * category disks (inventory, checklist, qr, attachments). Never hard-codes an
 * absolute path — the disk root comes from config (local / network share / custom).
 */
class FileStorage
{
    /** Store an uploaded file on the category disk; returns the stored path. */
    public function put(string $category, UploadedFile $file): string
    {
        $this->guardCategory($category);

        return $file->store('', $category);
    }

    public function delete(string $category, ?string $path): void
    {
        if (empty($path)) {
            return;
        }

        $this->guardCategory($category);
        Storage::disk($category)->delete($path);
    }

    public function exists(string $category, ?string $path): bool
    {
        if (empty($path)) {
            return false;
        }

        $this->guardCategory($category);

        return Storage::disk($category)->exists($path);
    }

    protected function guardCategory(string $category): void
    {
        if (! in_array($category, config('eams.storage_categories', []), true)) {
            throw new \InvalidArgumentException("Unknown storage category: {$category}");
        }
    }
}
