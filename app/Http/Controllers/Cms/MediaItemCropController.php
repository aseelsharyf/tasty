<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\MediaItem;
use App\Models\MediaItemCrop;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;

class MediaItemCropController extends Controller
{
    /**
     * Get all crop versions for a media item.
     */
    public function index(MediaItem $media): JsonResponse
    {
        $crops = $media->crops()
            ->with('media')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (MediaItemCrop $crop) => $this->formatCrop($crop));

        return response()->json([
            'crops' => $crops,
            'presets' => Setting::getCropPresets(),
        ]);
    }

    /**
     * Create a new crop version.
     */
    public function store(Request $request, MediaItem $media): JsonResponse
    {
        // Only allow crops on images
        if (! $media->is_image) {
            return response()->json(['error' => 'Crops can only be created for images.'], 422);
        }

        // Check for unsupported image formats
        $unsupportedError = $this->checkUnsupportedFormat($media);
        if ($unsupportedError) {
            return response()->json(['error' => $unsupportedError], 422);
        }

        $validated = $request->validate([
            'preset_name' => ['required', 'string', 'max:50'],
            'label' => ['nullable', 'string', 'max:255'],
            'crop_x' => ['required', 'numeric', 'min:0', 'max:100'],
            'crop_y' => ['required', 'numeric', 'min:0', 'max:100'],
            'crop_width' => ['required', 'numeric', 'min:1', 'max:100'],
            'crop_height' => ['required', 'numeric', 'min:1', 'max:100'],
        ]);

        // Get the preset configuration
        $presets = Setting::getCropPresets();
        $preset = collect($presets)->firstWhere('name', $validated['preset_name']);

        if (! $preset) {
            return response()->json(['error' => 'Invalid preset name.'], 422);
        }

        // Create crop record
        $crop = $media->crops()->create([
            'preset_name' => $validated['preset_name'],
            'preset_label' => $preset['label'],
            'label' => $validated['label'] ?? null,
            'crop_x' => $validated['crop_x'],
            'crop_y' => $validated['crop_y'],
            'crop_width' => $validated['crop_width'],
            'crop_height' => $validated['crop_height'],
            'output_width' => $preset['width'],
            'output_height' => $preset['height'],
            'created_by' => Auth::id(),
        ]);

        // Generate the cropped image - if this fails, delete the crop record
        try {
            $this->generateCroppedImage($media, $crop);
        } catch (\Throwable $e) {
            $crop->delete();
            report($e); // Log the error for debugging

            return response()->json([
                'error' => 'Failed to generate cropped image. The image format may not be supported or the image may be corrupted.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'crop' => $this->formatCrop($crop->fresh(['media'])),
        ]);
    }

    /**
     * Update an existing crop version.
     */
    public function update(Request $request, MediaItem $media, MediaItemCrop $crop): JsonResponse
    {
        if ($crop->media_item_id !== $media->id) {
            return response()->json(['error' => 'Crop does not belong to this media item.'], 403);
        }

        // Check for unsupported image formats
        $unsupportedError = $this->checkUnsupportedFormat($media);
        if ($unsupportedError) {
            return response()->json(['error' => $unsupportedError], 422);
        }

        $validated = $request->validate([
            'label' => ['nullable', 'string', 'max:255'],
            'crop_x' => ['required', 'numeric', 'min:0', 'max:100'],
            'crop_y' => ['required', 'numeric', 'min:0', 'max:100'],
            'crop_width' => ['required', 'numeric', 'min:1', 'max:100'],
            'crop_height' => ['required', 'numeric', 'min:1', 'max:100'],
        ]);

        // Store old values in case we need to rollback
        $oldValues = [
            'crop_x' => $crop->crop_x,
            'crop_y' => $crop->crop_y,
            'crop_width' => $crop->crop_width,
            'crop_height' => $crop->crop_height,
        ];

        $crop->update([
            'label' => $validated['label'] ?? null,
            'crop_x' => $validated['crop_x'],
            'crop_y' => $validated['crop_y'],
            'crop_width' => $validated['crop_width'],
            'crop_height' => $validated['crop_height'],
        ]);

        // Regenerate the cropped image - if this fails, rollback the update
        try {
            $this->generateCroppedImage($media, $crop);
        } catch (\Throwable $e) {
            // Rollback crop values
            $crop->update($oldValues);
            report($e);

            return response()->json([
                'error' => 'Failed to regenerate cropped image. The image format may not be supported or the image may be corrupted.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'crop' => $this->formatCrop($crop->fresh(['media'])),
        ]);
    }

    /**
     * Delete a crop version.
     */
    public function destroy(MediaItem $media, MediaItemCrop $crop): JsonResponse
    {
        if ($crop->media_item_id !== $media->id) {
            return response()->json(['error' => 'Crop does not belong to this media item.'], 403);
        }

        $crop->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Check if the image format is unsupported for cropping.
     */
    private function checkUnsupportedFormat(MediaItem $media): ?string
    {
        $originalMedia = $media->getFirstMedia('default');
        if (! $originalMedia) {
            return 'Original image not found.';
        }

        $extension = strtolower(pathinfo($originalMedia->getPath(), PATHINFO_EXTENSION));
        $mimeType = strtolower($originalMedia->mime_type ?? '');

        // Check GD support for various formats
        $gdInfo = function_exists('gd_info') ? gd_info() : [];

        $unsupportedFormats = [];

        // AVIF - check if GD has AVIF support
        if (in_array($extension, ['avif']) || str_contains($mimeType, 'avif')) {
            if (empty($gdInfo['AVIF Support'])) {
                return 'AVIF images cannot be cropped. The server does not support AVIF image processing. Please convert to JPEG or PNG first.';
            }
        }

        // HEIC/HEIF - GD doesn't support these
        if (in_array($extension, ['heic', 'heif']) || str_contains($mimeType, 'heic') || str_contains($mimeType, 'heif')) {
            return 'HEIC/HEIF images cannot be cropped. Please convert to JPEG or PNG first.';
        }

        // WebP - check if GD has WebP support
        if ($extension === 'webp' || str_contains($mimeType, 'webp')) {
            if (empty($gdInfo['WebP Support'])) {
                return 'WebP images cannot be cropped. The server does not support WebP image processing. Please convert to JPEG or PNG first.';
            }
        }

        return null;
    }

    /**
     * Generate the actual cropped image file using Spatie Image.
     */
    private function generateCroppedImage(MediaItem $mediaItem, MediaItemCrop $crop): void
    {
        $originalMedia = $mediaItem->getFirstMedia('default');
        if (! $originalMedia) {
            return;
        }

        // Get original image path
        $originalPath = $originalMedia->getPath();

        // Calculate pixel coordinates
        $pixels = $crop->getCropPixels($mediaItem->width, $mediaItem->height);

        // Generate a temp path for the cropped image (output as JPEG for best compatibility)
        $tempPath = sys_get_temp_dir().'/'.uniqid('crop_').'.jpg';

        // Use Spatie Image to crop, resize, and save as JPEG
        Image::load($originalPath)
            ->manualCrop($pixels['width'], $pixels['height'], $pixels['x'], $pixels['y'])
            ->fit(Fit::Contain, $crop->output_width, $crop->output_height)
            ->quality(90)
            ->save($tempPath);

        // Clear existing media and add new cropped image
        $crop->clearMediaCollection('crop');
        $crop->addMedia($tempPath)
            ->toMediaCollection('crop');
    }

    /**
     * Format crop for JSON response.
     *
     * @return array<string, mixed>
     */
    private function formatCrop(MediaItemCrop $crop): array
    {
        return [
            'id' => $crop->id,
            'uuid' => $crop->uuid,
            'preset_name' => $crop->preset_name,
            'preset_label' => $crop->preset_label,
            'label' => $crop->label,
            'display_label' => $crop->display_label,
            'crop_x' => (float) $crop->crop_x,
            'crop_y' => (float) $crop->crop_y,
            'crop_width' => (float) $crop->crop_width,
            'crop_height' => (float) $crop->crop_height,
            'output_width' => $crop->output_width,
            'output_height' => $crop->output_height,
            'url' => $crop->url,
            'thumbnail_url' => $crop->thumbnail_url,
            'created_at' => $crop->created_at,
        ];
    }
}
