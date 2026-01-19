<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class OgImageService
{
    protected int $width = 1200;

    protected int $height = 630;

    protected string $disk;

    public function __construct()
    {
        // Use the same disk as media library for CDN support
        $this->disk = config('media-library.disk_name', 'public');
    }

    /**
     * Generate an OG image for a post.
     */
    public function generateForPost(Post $post): ?string
    {
        if (! $post->featured_image_url) {
            return null;
        }

        $filename = 'og-images/posts/'.$post->slug.'.png';

        // Check if OG image already exists and is newer than the post
        if (Storage::disk($this->disk)->exists($filename)) {
            $ogImageTime = Storage::disk($this->disk)->lastModified($filename);
            if ($ogImageTime > $post->updated_at->timestamp) {
                return Storage::disk($this->disk)->url($filename);
            }
        }

        try {
            // Load the featured image from URL
            $imageContents = file_get_contents($post->featured_image_url);
            if (! $imageContents) {
                return null;
            }

            $image = Image::read($imageContents);

            // Resize and crop to OG dimensions, respecting focal point
            $this->coverWithFocalPoint($image, $post->featured_image_anchor);

            // Create gradient overlay using GD
            $this->applyGradientOverlay($image);

            // Add logo at bottom left
            $this->addLogo($image);

            // Save to disk (works with both local and cloud storage)
            $pngData = $image->toPng()->toString();
            Storage::disk($this->disk)->put($filename, $pngData, 'public');

            return Storage::disk($this->disk)->url($filename);
        } catch (\Exception $e) {
            report($e);

            return null;
        }
    }

    /**
     * Cover image to target dimensions respecting focal point.
     *
     * @param  array{x: float, y: float}|null  $focalPoint
     */
    protected function coverWithFocalPoint(\Intervention\Image\Interfaces\ImageInterface $image, ?array $focalPoint): void
    {
        $origWidth = $image->width();
        $origHeight = $image->height();

        // Calculate scale to cover target dimensions
        $scaleX = $this->width / $origWidth;
        $scaleY = $this->height / $origHeight;
        $scale = max($scaleX, $scaleY);

        // Scale image to cover
        $scaledWidth = (int) round($origWidth * $scale);
        $scaledHeight = (int) round($origHeight * $scale);
        $image->resize($scaledWidth, $scaledHeight);

        // Default focal point: center horizontally, top vertically
        $focalX = $focalPoint['x'] ?? 0.5;
        $focalY = $focalPoint['y'] ?? 0.0;

        // Calculate crop offset based on focal point
        // The focal point should remain visible, ideally centered in the crop
        $maxOffsetX = $scaledWidth - $this->width;
        $maxOffsetY = $scaledHeight - $this->height;

        // Position the crop so focal point is as centered as possible
        $offsetX = (int) round(($focalX * $scaledWidth) - ($this->width / 2));
        $offsetY = (int) round(($focalY * $scaledHeight) - ($this->height / 2));

        // Clamp offsets to valid range
        $offsetX = max(0, min($maxOffsetX, $offsetX));
        $offsetY = max(0, min($maxOffsetY, $offsetY));

        // Crop to final dimensions
        $image->crop($this->width, $this->height, $offsetX, $offsetY);
    }

    /**
     * Apply gradient overlay directly on the image using GD.
     */
    protected function applyGradientOverlay(\Intervention\Image\Interfaces\ImageInterface $image): void
    {
        // Get the underlying GD resource
        $gdImage = imagecreatetruecolor($this->width, $this->height);
        imagesavealpha($gdImage, true);
        imagealphablending($gdImage, false);

        // Fill with transparent
        $transparent = imagecolorallocatealpha($gdImage, 0, 0, 0, 127);
        imagefill($gdImage, 0, 0, $transparent);

        imagealphablending($gdImage, true);

        // Yellow color (#ffe762)
        $r = 255;
        $g = 231;
        $b = 98;

        // Gradient covers bottom 40% of image
        $gradientHeight = (int) ($this->height * 0.9);

        // Draw gradient from bottom
        for ($y = 0; $y < $gradientHeight; $y++) {
            $progress = $y / $gradientHeight;
            // Alpha: 40 at bottom (more visible) to 127 at top (transparent)
            $alpha = (int) (40 + ($progress * 87));

            $color = imagecolorallocatealpha($gdImage, $r, $g, $b, $alpha);
            imageline($gdImage, 0, $this->height - 1 - $y, $this->width, $this->height - 1 - $y, $color);
        }

        // Save gradient to temp and overlay
        $tempPath = sys_get_temp_dir().'/gradient_'.uniqid().'.png';
        imagepng($gdImage, $tempPath);
        imagedestroy($gdImage);

        $gradient = Image::read($tempPath);
        $image->place($gradient, 'top-left', 0, 0);
        unlink($tempPath);
    }

    /**
     * Add the Tasty logo to the bottom left of the image.
     */
    protected function addLogo(\Intervention\Image\Interfaces\ImageInterface $image): void
    {
        $logoPath = public_path('images/tasty-logo-black.png');

        if (! file_exists($logoPath)) {
            return;
        }

        try {
            $logo = Image::read($logoPath);
            $logo->scale(width: 200);

            $image->place($logo, 'bottom-left', 50, 50);
        } catch (\Exception $e) {
            report($e);
        }
    }

    /**
     * Get the OG image URL for a post, generating if needed.
     */
    public function getUrlForPost(Post $post): ?string
    {
        $filename = 'og-images/posts/'.$post->slug.'.png';

        // Check if already exists
        if (Storage::disk($this->disk)->exists($filename)) {
            return Storage::disk($this->disk)->url($filename);
        }

        // Generate new one
        return $this->generateForPost($post);
    }

    /**
     * Delete OG image for a post.
     */
    public function deleteForPost(Post $post): void
    {
        $filename = 'og-images/posts/'.$post->slug.'.png';
        Storage::disk($this->disk)->delete($filename);
    }
}
