<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use Illuminate\Support\Facades\Storage;

final class S3Service
{
    public function upload(string $path, $file, ?string $disk = 's3'): string
    {
        $fullPath = Storage::disk($disk)->put($path, $file);

        return Storage::disk($disk)->url($fullPath);
    }

    public function delete(string $path, ?string $disk = 's3'): bool
    {
        return Storage::disk($disk)->delete($path);
    }

    public function getUrl(string $path, ?string $disk = 's3'): string
    {
        return Storage::disk($disk)->url($path);
    }

    public function exists(string $path, ?string $disk = 's3'): bool
    {
        return Storage::disk($disk)->exists($path);
    }
}
