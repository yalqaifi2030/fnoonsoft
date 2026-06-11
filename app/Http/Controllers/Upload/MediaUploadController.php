<?php

namespace App\Http\Controllers\Upload;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Services\Upload\AssetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Direct upload for images & PDF (small, public, hotlinkable). Unlike the
 * archive engine these don't need multipart — one POST stores the file on the
 * public disk, derives image variants, and returns the share kit.
 */
class MediaUploadController extends Controller
{
    public function __construct(private readonly AssetService $assets)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => [
                'required', 'file',
                'mimes:jpg,jpeg,png,gif,webp,svg,pdf',
                'max:'.(int) env('MEDIA_MAX_KB', 51200), // 50 MB
            ],
        ]);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $isPdf = $ext === 'pdf';

        $slug = $this->assets->newSlug();
        $dir = 'assets/'.date('Y/m');
        $path = $file->storeAs($dir, $slug.'.'.$ext, ['disk' => 'public']);

        $width = $height = $variants = $pages = null;

        if ($isPdf) {
            $pages = $this->assets->pdfPageCount(Storage::disk('public')->path($path));
        } else {
            // Stamp the watermark first so every derived variant is protected too.
            app(\App\Services\WatermarkService::class)->apply(Storage::disk('public')->path($path));

            $img = $this->assets->processImage($path);
            $width = $img['width'];
            $height = $img['height'];
            $variants = $img['variants'];
        }

        $asset = Asset::create([
            'slug' => $slug,
            'user_id' => $request->user()?->id,
            'kind' => $isPdf ? 'pdf' : 'image',
            'disk' => 'public',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'width' => $width,
            'height' => $height,
            'variants' => $variants,
            'pages' => $pages,
            'is_active' => true,
        ]);

        return response()->json($this->payload($asset));
    }

    /** Consistent JSON shape used by the panel to render the share kit. */
    private function payload(Asset $asset): array
    {
        return [
            'slug' => $asset->slug,
            'kind' => $asset->kind,
            'name' => $asset->original_name,
            'size' => $asset->size_bytes,
            'preview' => $asset->isImage() ? ($asset->thumbUrl() ?: $asset->directUrl()) : null,
            'direct' => $asset->directUrl(),
            'page' => $asset->pageUrl(),
            'download' => $asset->downloadUrl(),
            'pages' => $asset->pages,
            'kit' => $this->assets->shareKit($asset),
        ];
    }
}
