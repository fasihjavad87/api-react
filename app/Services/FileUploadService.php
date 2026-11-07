<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{
    /**
     * آپلود فایل و ساخت رکورد Media
     */
    public static function storeMedia(
        UploadedFile $file,
        string $folder = 'media',
        bool $useUuidName = true,
        bool $useDatePath = true,
        ?string $customPath = null,
        array $meta = []
    ): Media {
        // ساخت مسیر
        $pathParts = [$folder];

        if ($customPath) {
            $pathParts[] = trim($customPath, '/');
        } elseif ($useDatePath) {
            $pathParts[] = date('Y');
            $pathParts[] = date('n');
        }

        $path = implode('/', $pathParts);

        // ساخت نام فایل
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        if ($useUuidName) {
            $filename = (string) Str::uuid();
        } else {
            $filename = Str::slug($originalName) . '-' . uniqid();
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $fullFileName = $filename . '.' . $extension;

        // ذخیره فایل
        $storedPath = $file->storeAs($path, $fullFileName, 'public');

        // ساخت رکورد Media
        return Media::create([
            'name'        => $originalName,
            'file_name'   => $fullFileName,
            'slug'        => Str::slug($originalName) . '-' . Str::random(6),
            'path'        => $storedPath,
            'disk'        => 'public',
            'mime_type'   => $file->getClientMimeType(),
            'extension'   => $extension,
            'size'        => $file->getSize(),
            'title'       => $meta['title'] ?? null,
            'description' => $meta['description'] ?? null,
            'user_id'     => $meta['user_id'] ?? auth()->id(),
        ]);
    }

    /**
     * حذف فایل از استوریج
     */
    public static function deleteFile(?string $path, string $disk = 'public'): bool
    {
        if (!$path) {
            return false;
        }

        if (Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->delete($path);
        }

        return false;
    }
}