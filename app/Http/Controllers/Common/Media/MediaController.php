<?php

namespace App\Http\Controllers\Common\Media;

use Illuminate\Http\Request;
use App\Models\Media\MediaFile;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Media\MediaFileVersion;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\MediaFileResource;
use App\Http\Resources\MediaFileCollection;

class MediaController extends Controller
{


public function index(Request $request)
{
    $query = MediaFile::with(['versions' => function ($q) {
        $q->where('label', 'original');
    }])->latest();

    // Filter by MediaFile name
    if ($request->filled('name')) {
        $query->where('name', 'like', '%' . $request->name . '%');
    }

    // Filter by original version properties
    $query->whereHas('versions', function ($q) use ($request) {
        $q->where('label', 'original');

        if ($request->filled('size')) {
            $q->where('size', $request->size);
        }

        if ($request->filled('size_type')) {
            $q->where('size_type', 'like', '%' . $request->size_type . '%');
        }

        if ($request->filled('type')) {
            $q->where('type', $request->type);
        }

        if ($request->filled('dimensions')) {
            $q->where('dimensions', $request->dimensions);
        }
    });

    // Sorting
    if ($request->filled('sort_by') && in_array($request->sort_by, ['name', 'size', 'dimensions'])) {
        $sortBy = $request->sort_by;

        if ($sortBy === 'name') {
            $query->orderBy('name', $request->input('sort_order', 'asc'));
        } else {
            // Sorting by size or dimensions from original version
            $query->join('media_file_versions as v', function ($join) {
                $join->on('media_files.id', '=', 'v.media_file_id')
                     ->where('v.label', 'original');
            });

            $query->orderBy('v.' . $sortBy, $request->input('sort_order', 'asc'))
                  ->select('media_files.*'); // avoid selecting join table fields
        }
    }

    // Dynamic per_page, default 20
    $perPage = $request->input('per_page', 20);
    $mediaFiles = $query->paginate($perPage);

    return response()->json([
        'data' => new MediaFileCollection($mediaFiles),
    ]);
}


public function show($id)
{
    $media = MediaFile::with('versions')->find($id);

    if (!$media) {
        return response()->json([
            'Message' => 'Media file not found',
        ], 404);
    }

    return response()->json([
        'data' => new MediaFileResource($media),
    ]);
}


    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|image|max:10240', // 10MB
        ]);

        if (!extension_loaded('gd')) {
            Log::error("GD library is NOT available!");
            return response()->json([
                'Message' => 'GD library not available',

            ], 500);
        }

        $file = $request->file('file');
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = strtolower($file->getClientOriginalExtension());
        $filename = uniqid() . '.' . $extension;
        $disk = 'public';
        $path = 'uploads/images/';

        // Save original
        Storage::disk($disk)->put($path . $filename, file_get_contents($file));
        $originalUrl = Storage::url($path . $filename);

        $media = MediaFile::create([
            'name' => $originalName,
            'original_url' => $originalUrl,
        ]);

        // Define required sizes
        $sizes = [
            'original' => null,      // keep full size
            'thumbnail' => [150, 150],
            'medium' => [300, 300],
            'large' => [600, 600],          // proportional
            'extra_large' => [1200, 1200],  // proportional
            'xlarge' => [1800, 1800],       // proportional
            'square_50' => [50, 50],
            'square_100' => [100, 100],
            'square_200' => [200, 200],
            'square_400' => [400, 400],
            'square_600' => [600, 600],
            'square_800' => [800, 800],
            'square_1000' => [1000, 1000],
        ];

        foreach ($sizes as $label => $dimensions) {
            if ($label === 'original') {
                $sizeInBytes = $file->getSize();
                $sizeType = $this->formatSize($sizeInBytes);
                [$width, $height] = getimagesize($file->getPathname());
                $url = $originalUrl;
            } else {
                $versionFilename = $label . '_' . $filename;

                // For square sizes, resize exactly; for others, keep ratio
                if (str_starts_with($label, 'square_')) {
                    [$resizedContent, $width, $height] = $this->resizeImageGDSquare(
                        $file->getPathname(),
                        $dimensions[0],
                        $extension
                    );
                } else {
                    [$resizedContent, $width, $height] = $this->resizeImageGDKeepRatio(
                        $file->getPathname(),
                        $dimensions[0],
                        $dimensions[1],
                        $extension
                    );
                }

                Storage::disk($disk)->put($path . $versionFilename, $resizedContent);
                $sizeInBytes = Storage::disk($disk)->size($path . $versionFilename);
                $sizeType = $this->formatSize($sizeInBytes);
                $url = Storage::url($path . $versionFilename);
            }

            MediaFileVersion::create([
                'media_file_id' => $media->id,
                'label' => $label,
                'url' => $url,
                'size' => $sizeInBytes,
                'size_type' => $sizeType,
                'type' => $file->getMimeType(),
                'dimensions' => $width . 'x' . $height,
            ]);
        }

        $media->load('versions');

        return response()->json([
            'data' => new MediaFileResource($media),
        ]);
    }

    private function formatSize($bytes)
    {
        $kb = 1024;
        $mb = 1024 * 1024;
        if ($bytes >= $mb) return round($bytes / $mb, 2) . ' MB';
        if ($bytes >= $kb) return round($bytes / $kb, 2) . ' KB';
        return $bytes . ' bytes';
    }

    // Keep aspect ratio
    private function resizeImageGDKeepRatio($filePath, $maxWidth, $maxHeight, $extension)
    {
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $src = imagecreatefromjpeg($filePath);
                break;
            case 'png':
                $src = imagecreatefrompng($filePath);
                break;
            case 'gif':
                $src = imagecreatefromgif($filePath);
                break;
            default:
                throw new \Exception("Unsupported image type: $extension");
        }

        $origWidth = imagesx($src);
        $origHeight = imagesy($src);
        $ratio = min($maxWidth / $origWidth, $maxHeight / $origHeight);
        $newWidth = (int)($origWidth * $ratio);
        $newHeight = (int)($origHeight * $ratio);

        $tmp = imagecreatetruecolor($newWidth, $newHeight);

        if ($extension === 'png' || $extension === 'gif') {
            imagecolortransparent($tmp, imagecolorallocatealpha($tmp, 0, 0, 0, 127));
            imagealphablending($tmp, false);
            imagesavealpha($tmp, true);
        }

        imagecopyresampled($tmp, $src, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

        ob_start();
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($tmp, null, 90);
                break;
            case 'png':
                imagepng($tmp);
                break;
            case 'gif':
                imagegif($tmp);
                break;
        }
        $imageData = ob_get_clean();

        imagedestroy($src);
        imagedestroy($tmp);

        return [$imageData, $newWidth, $newHeight];
    }

    // Exact square resize
    private function resizeImageGDSquare($filePath, $size, $extension)
    {
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $src = imagecreatefromjpeg($filePath);
                break;
            case 'png':
                $src = imagecreatefrompng($filePath);
                break;
            case 'gif':
                $src = imagecreatefromgif($filePath);
                break;
            default:
                throw new \Exception("Unsupported image type: $extension");
        }

        $tmp = imagecreatetruecolor($size, $size);

        if ($extension === 'png' || $extension === 'gif') {
            imagecolortransparent($tmp, imagecolorallocatealpha($tmp, 0, 0, 0, 127));
            imagealphablending($tmp, false);
            imagesavealpha($tmp, true);
        }

        $origWidth = imagesx($src);
        $origHeight = imagesy($src);

        // Center crop for square
        $minSide = min($origWidth, $origHeight);
        $srcX = (int)(($origWidth - $minSide) / 2);
        $srcY = (int)(($origHeight - $minSide) / 2);

        imagecopyresampled($tmp, $src, 0, 0, $srcX, $srcY, $size, $size, $minSide, $minSide);

        ob_start();
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($tmp, null, 90);
                break;
            case 'png':
                imagepng($tmp);
                break;
            case 'gif':
                imagegif($tmp);
                break;
        }
        $imageData = ob_get_clean();

        imagedestroy($src);
        imagedestroy($tmp);

        return [$imageData, $size, $size];
    }
}
