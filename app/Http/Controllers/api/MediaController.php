<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Media;
use App\Services\FileUploadService;
use Illuminate\Validation\ValidationException;

class MediaController extends Controller
{
    /**
     * لیست رسانه‌ها با صفحه‌بندی و جستجو
     */
    public function index(Request $request)
    {
        $perPage = min(
            max((int) $request->input('per_page', 50), 1),
            100
        );

        $query = Media::query();

        // 🔍 جستجو
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $media = $query->latest('id')->paginate($perPage);

        return response()->json($media);
    }

    /**
     * لیست رسانه‌های حذف‌شده (سبد حذف) با صفحه‌بندی و جستجو
     */
    public function trashed(Request $request)
    {
        $perPage = min(
            max((int) $request->input('per_page', 50), 1),
            100
        );

        $query = Media::onlyTrashed();

        // 🔍 جستجو
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $media = $query->latest('id')->paginate($perPage);

        return response()->json($media);
    }

    /**
     * نمایش یک رسانه
     */
    public function show(int $id)
    {
        $media = Media::withTrashed()->findOrFail($id);

        return response()->json([
            'data' => $media,
            'url'  => $media->getUrl(),
        ]);
    }

    /**
     * آپلود یک یا چند رسانه
     *
     * دو حالت پشتیبانی می‌شود:
     * 1) files[]   → چند فایل (multi upload)
     * 2) file      → یک فایل (backward compatible)
     */
    public function store(Request $request)
    {
        $request->validate([
            'files'        => ['required_without:file', 'array', 'min:1'],
            'files.*'      => ['file', 'max:51200'],
            'file'         => ['required_without:files', 'file', 'max:51200'],
            'titles'       => ['nullable', 'array'],
            'titles.*'     => ['nullable', 'string', 'max:255'],
            'title'        => ['nullable', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'folder'       => ['nullable', 'string', 'max:100'],
        ]);

        // 🔹 نرمال‌سازی فایل‌ها
        $files = $request->hasFile('files')
            ? $request->file('files')
            : [$request->file('file')];

        // 🔹 نرمال‌سازی title ها (آرایه موازی با files)
        $titles = $request->input('titles', []);
        if (!is_array($titles)) {
            $titles = [];
        }

        // اگه تکی بود و title ساده اومده بود
        if (count($files) === 1 && empty($titles) && $request->filled('title')) {
            $titles = [$request->input('title')];
        }

        $folder = $request->input('folder', 'media');
        $userId = $request->user()?->id;
        $desc   = $request->input('description');

        $uploaded = [];
        $errors   = [];

        foreach ($files as $index => $file) {
            try {
                // title مربوط به همین فایل (اگه بود)
                $fileTitle = $titles[$index] ?? null;
                // اگه خالی بود، null ذخیره کن
                $fileTitle = ($fileTitle !== null && trim($fileTitle) !== '')
                    ? trim($fileTitle)
                    : null;

                $media = FileUploadService::storeMedia(
                    file: $file,
                    folder: $folder,
                    meta: [
                        'title'       => $fileTitle,
                        'description' => count($files) === 1 ? $desc : null,
                        'user_id'     => $userId,
                    ]
                );

                $uploaded[] = [
                    'data' => $media,
                    'url'  => $media->getUrl(),
                ];
            } catch (\Throwable $e) {
                $errors[] = [
                    'file'  => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ];
            }
        }

        if (empty($uploaded)) {
            throw ValidationException::withMessages([
                'files'  => 'هیچ فایلی با موفقیت آپلود نشد.',
                'errors' => $errors,
            ]);
        }

        return response()->json([
            'message'  => count($uploaded) . ' فایل با موفقیت آپلود شد.',
            'data'     => $uploaded,
            'uploaded' => count($uploaded),
            'failed'   => count($errors),
            'errors'   => $errors,
        ], 201);
    }

    /**
     * ویرایش متادیتای رسانه (فایل قابل تغییر نیست)
     */
    public function update(Request $request, int $id)
    {
        $media = Media::withTrashed()->findOrFail($id);

        $data = $request->validate([
            'name'        => ['sometimes', 'string', 'max:255'],
            'title'       => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'slug'        => ['nullable', 'string', 'max:255', 'unique:media,slug,' . $media->id],
        ]);

        $media->update($data);

        return response()->json([
            'message' => 'رسانه با موفقیت ویرایش شد.',
            'data'    => $media->fresh(),
        ]);
    }

    /**
     * حذف نرم یک رسانه
     */
    public function destroy(int $id)
    {
        $media = Media::findOrFail($id);
        $media->delete();

        return response()->json([
            'message' => 'رسانه به سبد حذف منتقل شد.',
        ]);
    }

    /**
     * بازگردانی یک رسانه حذف‌شده
     */
    public function restore(int $id)
    {
        $media = Media::onlyTrashed()->findOrFail($id);
        $media->restore();

        return response()->json([
            'message' => 'رسانه بازگردانی شد.',
            'data'    => $media,
        ]);
    }

    /**
     * حذف کامل (فایل + رکورد)
     */
    public function forceDelete(int $id)
    {
        $media = Media::onlyTrashed()->findOrFail($id);

        FileUploadService::deleteFile($media->path, $media->disk);

        $media->forceDelete();

        return response()->json([
            'message' => 'رسانه برای همیشه حذف شد.',
        ]);
    }

    /**
     * حذف گروهی (نرم)
     */
    public function groupDelete(Request $request)
    {
        $data = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:media,id'],
        ]);

        Media::whereIn('id', $data['ids'])->delete();

        return response()->json([
            'message' => 'رسانه‌های انتخاب‌شده حذف شدند.',
        ]);
    }

    /**
     * بازگردانی گروهی
     */
    public function groupRestore(Request $request)
    {
        $data = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        Media::onlyTrashed()
            ->whereIn('id', $data['ids'])
            ->restore();

        return response()->json([
            'message' => 'رسانه‌های انتخاب‌شده بازگردانی شدند.',
        ]);
    }

    /**
     * حذف کامل گروهی (فایل + رکورد)
     */
    public function groupForceDelete(Request $request)
    {
        $data = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $items = Media::onlyTrashed()
            ->whereIn('id', $data['ids'])
            ->get();

        foreach ($items as $item) {
            FileUploadService::deleteFile($item->path, $item->disk);
            $item->forceDelete();
        }

        return response()->json([
            'message' => 'رسانه‌های انتخاب‌شده برای همیشه حذف شدند.',
        ]);
    }
}
