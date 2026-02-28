<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Laravel\Facades\Image;

class OgImageService
{
    protected int $width = 1200;

    protected int $height = 630;

    protected string $disk;

    protected string $pathPrefix;

    // Tasty brand colors
    protected array $yellow = ['r' => 255, 'g' => 231, 'b' => 98]; // #ffe762

    protected array $blueBlack = ['r' => 27, 'g' => 27, 'b' => 27]; // #1b1b1b

    public function __construct()
    {
        // Use the same disk as media library for CDN support
        $this->disk = config('media-library.disk_name', 'public');
        // Use media prefix for environment separation (production/staging/develop)
        $this->pathPrefix = config('media-library.prefix', '');
    }

    /**
     * Get the full path with environment prefix.
     */
    protected function getPath(string $filename): string
    {
        if ($this->pathPrefix) {
            return $this->pathPrefix.'/'.$filename;
        }

        return $filename;
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
        $fullPath = $this->getPath($filename);

        // Check if OG image already exists and is newer than the post
        if (Storage::disk($this->disk)->exists($fullPath)) {
            $ogImageTime = Storage::disk($this->disk)->lastModified($fullPath);
            if ($ogImageTime > $post->updated_at->timestamp) {
                return Storage::disk($this->disk)->url($fullPath);
            }
        }

        try {
            // Load the featured image from URL
            $imageContents = file_get_contents($post->featured_image_url);
            if (! $imageContents) {
                return null;
            }

            $image = $this->generateOgImage($imageContents, $post);

            // Save to disk (works with both local and cloud storage)
            $pngData = $image->toPng()->toString();
            Storage::disk($this->disk)->put($fullPath, $pngData, 'public');

            return Storage::disk($this->disk)->url($fullPath);
        } catch (\Exception $e) {
            report($e);

            return null;
        }
    }

    /**
     * Generate OG image with grayscale featured image, diagonal yellow gradient from bottom-right, and logo.
     */
    protected function generateOgImage(string $imageContents, Post $post): ImageInterface
    {
        $image = Image::read($imageContents);

        // Full-bleed: cover the entire canvas
        $this->coverWithFocalPoint($image, $post->featured_image_anchor);

        // Apply diagonal yellow gradient from bottom-right
        $this->addDiagonalGradient($image);

        // Add logo at bottom right
        $this->addLogo($image);

        return $image;
    }

    /**
     * Apply a diagonal yellow gradient in the bottom-right corner using GD.
     *
     * Matches the HTML template: a 1030x901 rectangle rotated 146.67deg with
     * CSS linear-gradient(175deg). Uses proper CSS gradient line calculation.
     */
    protected function addDiagonalGradient(ImageInterface $image): void
    {
        $tempPath = sys_get_temp_dir().'/gradient_'.uniqid().'.png';
        $image->toPng()->save($tempPath);

        $gdImage = imagecreatefrompng($tempPath);
        imagealphablending($gdImage, true);

        // Figma/CSS: 1029.70 x 900.70 rectangle, rotated 146.67deg,
        // container positioned at left=556.67, top=73.79 relative to OG canvas.
        $rectW = 1029.70;
        $rectH = 900.70;
        $centerX = 556.67 + $rectW / 2;
        $centerY = 73.79 + $rectH / 2;

        // Inverse rotation to transform canvas pixels into element-local space
        $rotationRad = deg2rad(146.67);
        $cosR = cos(-$rotationRad);
        $sinR = sin(-$rotationRad);
        $halfW = $rectW / 2;
        $halfH = $rectH / 2;

        // CSS linear-gradient(175deg) direction vector in element-local space (y-down).
        // CSS convention: 0deg = bottom-to-top, angles go clockwise.
        // Direction vector = (sin(angle), -cos(angle)) in screen coords.
        $cssGradRad = deg2rad(175.0);
        $gradDx = sin($cssGradRad);   // ~0.087
        $gradDy = -cos($cssGradRad);  // ~0.996

        // CSS gradient line length (per spec):
        // abs(width * sin(angle)) + abs(height * cos(angle))
        $gradLineLength = abs($rectW * sin($cssGradRad)) + abs($rectH * cos($cssGradRad));

        // Gradient stops from Figma
        $stops = [
            ['pos' => 0.19, 'alpha' => 0.881],
            ['pos' => 0.54, 'alpha' => 0.792],
            ['pos' => 0.58, 'alpha' => 0.725],
            ['pos' => 0.80, 'alpha' => 0.0],
        ];

        for ($y = 0; $y < $this->height; $y++) {
            for ($x = 0; $x < $this->width; $x++) {
                // Transform canvas pixel to element-local coordinate space
                $dx = $x - $centerX;
                $dy = $y - $centerY;
                $localX = $dx * $cosR - $dy * $sinR;
                $localY = $dx * $sinR + $dy * $cosR;

                // Check if pixel is inside the rectangle bounds
                if ($localX < -$halfW || $localX > $halfW || $localY < -$halfH || $localY > $halfH) {
                    continue;
                }

                // Project onto gradient direction in local space.
                // Gradient line centered at origin, runs from -length/2 to +length/2.
                $proj = $localX * $gradDx + $localY * $gradDy;
                $t = ($proj + $gradLineLength / 2) / $gradLineLength;

                // Interpolate opacity from gradient stops
                $opacity = 0.0;
                if ($t <= $stops[0]['pos']) {
                    $opacity = $stops[0]['alpha'];
                } elseif ($t >= $stops[count($stops) - 1]['pos']) {
                    $opacity = $stops[count($stops) - 1]['alpha'];
                } else {
                    for ($i = 0; $i < count($stops) - 1; $i++) {
                        if ($t >= $stops[$i]['pos'] && $t <= $stops[$i + 1]['pos']) {
                            $segProgress = ($t - $stops[$i]['pos']) / ($stops[$i + 1]['pos'] - $stops[$i]['pos']);
                            $opacity = $stops[$i]['alpha'] + $segProgress * ($stops[$i + 1]['alpha'] - $stops[$i]['alpha']);
                            break;
                        }
                    }
                }

                if ($opacity <= 0.01) {
                    continue;
                }

                // GD alpha: 0 = opaque, 127 = transparent
                $gdAlpha = (int) (127 * (1 - $opacity));
                $color = imagecolorallocatealpha(
                    $gdImage,
                    $this->yellow['r'],
                    $this->yellow['g'],
                    $this->yellow['b'],
                    $gdAlpha
                );
                imagesetpixel($gdImage, $x, $y, $color);
            }
        }

        imagepng($gdImage, $tempPath);
        imagedestroy($gdImage);

        $newImage = Image::read($tempPath);
        $image->place($newImage, 'top-left', 0, 0);
        unlink($tempPath);
    }

    /**
     * Add kicker and title text to the canvas.
     */
    protected function addText(ImageInterface $canvas, Post $post, int $textX, int $textAreaWidth): void
    {
        $fontPath = resource_path('fonts/new-spirit-condensed.ttf');
        if (! file_exists($fontPath)) {
            return;
        }

        // Get kicker or fallback to category
        $kicker = $post->kicker;
        if (! $kicker && $category = $post->categories->first()) {
            $kicker = $category->name;
        }

        // Calculate title size based on length
        $titleLength = mb_strlen($post->title);
        $titleSize = match (true) {
            $titleLength > 100 => 32,
            $titleLength > 70 => 38,
            $titleLength > 50 => 44,
            default => 48,
        };

        // Center content vertically
        $centerY = $this->height / 2;
        $currentY = $centerY - 60;

        // Add kicker
        if ($kicker) {
            $kickerText = mb_strtoupper($kicker);
            $this->drawText($canvas, $kickerText, $textX, $currentY, 24, $fontPath);
            $currentY += 50;
        }

        // Add title (wrapped)
        $wrappedTitle = $this->wrapText($post->title, $textAreaWidth - 20, $titleSize, $fontPath);
        $titleLines = explode("\n", $wrappedTitle);

        foreach ($titleLines as $line) {
            $this->drawText($canvas, $line, $textX, $currentY, $titleSize, $fontPath);
            $currentY += $titleSize * 1.1;
        }
    }

    /**
     * Cover image to target dimensions respecting focal point.
     *
     * @param  array{x: float, y: float}|null  $focalPoint
     */
    protected function coverWithFocalPoint(ImageInterface $image, ?array $focalPoint, ?int $targetWidth = null, ?int $targetHeight = null): void
    {
        $targetWidth = $targetWidth ?? $this->width;
        $targetHeight = $targetHeight ?? $this->height;

        $origWidth = $image->width();
        $origHeight = $image->height();

        // Calculate scale to cover target dimensions
        $scaleX = $targetWidth / $origWidth;
        $scaleY = $targetHeight / $origHeight;
        $scale = max($scaleX, $scaleY);

        // Scale image to cover
        $scaledWidth = (int) round($origWidth * $scale);
        $scaledHeight = (int) round($origHeight * $scale);
        $image->resize($scaledWidth, $scaledHeight);

        // Default focal point: center horizontally, top vertically
        $focalX = $focalPoint['x'] ?? 0.5;
        $focalY = $focalPoint['y'] ?? 0.0;

        // Calculate crop offset based on focal point
        $maxOffsetX = $scaledWidth - $targetWidth;
        $maxOffsetY = $scaledHeight - $targetHeight;

        // Position the crop so focal point is as centered as possible
        $offsetX = (int) round(($focalX * $scaledWidth) - ($targetWidth / 2));
        $offsetY = (int) round(($focalY * $scaledHeight) - ($targetHeight / 2));

        // Clamp offsets to valid range
        $offsetX = max(0, min($maxOffsetX, $offsetX));
        $offsetY = max(0, min($maxOffsetY, $offsetY));

        // Crop to final dimensions
        $image->crop($targetWidth, $targetHeight, $offsetX, $offsetY);
    }

    /**
     * Create a canvas filled with Tasty yellow.
     */
    protected function createYellowCanvas(): ImageInterface
    {
        $canvas = imagecreatetruecolor($this->width, $this->height);
        $yellow = imagecolorallocate($canvas, $this->yellow['r'], $this->yellow['g'], $this->yellow['b']);
        imagefill($canvas, 0, 0, $yellow);

        $tempPath = sys_get_temp_dir().'/canvas_'.uniqid().'.png';
        imagepng($canvas, $tempPath);
        imagedestroy($canvas);

        $image = Image::read($tempPath);
        unlink($tempPath);

        return $image;
    }

    /**
     * Draw text on an image using GD.
     */
    protected function drawText(ImageInterface $canvas, string $text, float $x, float $y, int $size, string $fontPath): void
    {
        // Get the GD resource through a temp file round-trip
        $tempPath = sys_get_temp_dir().'/canvas_text_'.uniqid().'.png';
        $canvas->toPng()->save($tempPath);

        $gdImage = imagecreatefrompng($tempPath);
        $textColor = imagecolorallocate($gdImage, $this->blueBlack['r'], $this->blueBlack['g'], $this->blueBlack['b']);

        // imagettftext uses baseline Y, add the font size to position from top
        imagettftext($gdImage, $size, 0, (int) $x, (int) ($y + $size), $textColor, $fontPath, $text);

        imagepng($gdImage, $tempPath);
        imagedestroy($gdImage);

        // Read back and replace canvas contents
        $newCanvas = Image::read($tempPath);
        $canvas->place($newCanvas, 'top-left', 0, 0);
        unlink($tempPath);
    }

    /**
     * Wrap text to fit within a given width.
     */
    protected function wrapText(string $text, int $maxWidth, int $fontSize, string $fontPath): string
    {
        $words = explode(' ', $text);
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            $testLine = $currentLine === '' ? $word : $currentLine.' '.$word;
            $box = imagettfbbox($fontSize, 0, $fontPath, $testLine);
            $testWidth = $box[2] - $box[0];

            if ($testWidth <= $maxWidth) {
                $currentLine = $testLine;
            } else {
                if ($currentLine !== '') {
                    $lines[] = $currentLine;
                }
                $currentLine = $word;
            }
        }

        if ($currentLine !== '') {
            $lines[] = $currentLine;
        }

        return implode("\n", $lines);
    }

    /**
     * Apply rounded corners to an image.
     */
    protected function applyRoundedCorners(ImageInterface $image, int $radius): ImageInterface
    {
        $width = $image->width();
        $height = $image->height();

        // Save image to temp
        $tempImagePath = sys_get_temp_dir().'/rounded_src_'.uniqid().'.png';
        $image->toPng()->save($tempImagePath);
        $srcImage = imagecreatefrompng($tempImagePath);

        // Create a new image with transparency
        $roundedImage = imagecreatetruecolor($width, $height);
        imagesavealpha($roundedImage, true);
        imagealphablending($roundedImage, false);

        $transparent = imagecolorallocatealpha($roundedImage, 0, 0, 0, 127);
        imagefill($roundedImage, 0, 0, $transparent);

        imagealphablending($roundedImage, true);

        // Create rounded rectangle mask
        $mask = imagecreatetruecolor($width, $height);
        $maskTransparent = imagecolorallocate($mask, 0, 0, 0);
        $maskWhite = imagecolorallocate($mask, 255, 255, 255);
        imagefill($mask, 0, 0, $maskTransparent);

        // Draw rounded rectangle
        imagefilledrectangle($mask, $radius, 0, $width - $radius - 1, $height - 1, $maskWhite);
        imagefilledrectangle($mask, 0, $radius, $width - 1, $height - $radius - 1, $maskWhite);
        imagefilledellipse($mask, $radius, $radius, $radius * 2, $radius * 2, $maskWhite);
        imagefilledellipse($mask, $width - $radius - 1, $radius, $radius * 2, $radius * 2, $maskWhite);
        imagefilledellipse($mask, $radius, $height - $radius - 1, $radius * 2, $radius * 2, $maskWhite);
        imagefilledellipse($mask, $width - $radius - 1, $height - $radius - 1, $radius * 2, $radius * 2, $maskWhite);

        // Apply mask
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $maskColor = imagecolorat($mask, $x, $y);
                if ($maskColor === $maskWhite) {
                    $srcColor = imagecolorat($srcImage, $x, $y);
                    imagesetpixel($roundedImage, $x, $y, $srcColor);
                }
            }
        }

        imagedestroy($mask);
        imagedestroy($srcImage);
        unlink($tempImagePath);

        $resultPath = sys_get_temp_dir().'/rounded_result_'.uniqid().'.png';
        imagepng($roundedImage, $resultPath);
        imagedestroy($roundedImage);

        $result = Image::read($resultPath);
        unlink($resultPath);

        return $result;
    }

    /**
     * Add the Tasty logo to the bottom-right of the image.
     */
    protected function addLogo(ImageInterface $image): void
    {
        $logoPath = public_path('images/tasty-logo-black.png');

        if (! file_exists($logoPath)) {
            return;
        }

        try {
            $logo = Image::read($logoPath);
            $logo->scale(height: 56);

            $padding = 40;
            $logoX = $this->width - $logo->width() - $padding;
            $logoY = $this->height - $logo->height() - $padding;

            $image->place($logo, 'top-left', $logoX, $logoY);
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
        $fullPath = $this->getPath($filename);

        // Check if already exists
        if (Storage::disk($this->disk)->exists($fullPath)) {
            return Storage::disk($this->disk)->url($fullPath);
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
        $fullPath = $this->getPath($filename);
        Storage::disk($this->disk)->delete($fullPath);
    }

    /**
     * Generate a default OG image for general pages.
     */
    public function generateDefault(?string $title = null, ?string $subtitle = null, bool $force = false): ?string
    {
        $filename = 'og-images/default.png';
        $fullPath = $this->getPath($filename);

        if (! $force && Storage::disk($this->disk)->exists($fullPath)) {
            return Storage::disk($this->disk)->url($fullPath);
        }

        try {
            $canvas = $this->createYellowCanvas();

            // Add logo centered
            $this->addCenteredLogo($canvas);

            // Add title and subtitle if provided
            if ($title) {
                $this->addDefaultText($canvas, $title, $subtitle);
            }

            $pngData = $canvas->toPng()->toString();
            Storage::disk($this->disk)->put($fullPath, $pngData, 'public');

            return Storage::disk($this->disk)->url($fullPath);
        } catch (\Exception $e) {
            report($e);

            return null;
        }
    }

    /**
     * Add centered logo to canvas.
     */
    protected function addCenteredLogo(ImageInterface $canvas, string $position = 'center'): void
    {
        $logoPath = public_path('images/tasty-logo-black.png');

        if (! file_exists($logoPath)) {
            return;
        }

        try {
            $logo = Image::read($logoPath);
            $logo->scale(height: 80);

            $logoX = ($this->width - $logo->width()) / 2;

            if ($position === 'bottom') {
                $logoY = $this->height - 60 - $logo->height();
            } else {
                $logoY = ($this->height - $logo->height()) / 2;
            }

            $canvas->place($logo, 'top-left', (int) $logoX, (int) $logoY);
        } catch (\Exception $e) {
            report($e);
        }
    }

    /**
     * Add centered text for default pages.
     */
    protected function addDefaultText(ImageInterface $canvas, string $title, ?string $subtitle = null): void
    {
        $fontPath = resource_path('fonts/new-spirit-condensed.ttf');
        if (! file_exists($fontPath)) {
            return;
        }

        $titleSize = 64;
        $subtitleSize = 24;

        // Calculate title position (centered)
        $titleBox = imagettfbbox($titleSize, 0, $fontPath, $title);
        $titleWidth = $titleBox[2] - $titleBox[0];
        $titleX = ($this->width - $titleWidth) / 2;

        $centerY = $this->height / 2;
        $titleY = $subtitle ? $centerY - 40 : $centerY - ($titleSize / 2);

        $this->drawText($canvas, $title, $titleX, $titleY, $titleSize, $fontPath);

        // Add subtitle if provided
        if ($subtitle) {
            $subtitleText = mb_strtoupper($subtitle);
            $subtitleBox = imagettfbbox($subtitleSize, 0, $fontPath, $subtitleText);
            $subtitleWidth = $subtitleBox[2] - $subtitleBox[0];
            $subtitleX = ($this->width - $subtitleWidth) / 2;
            $subtitleY = $titleY - 50;

            $this->drawText($canvas, $subtitleText, $subtitleX, $subtitleY, $subtitleSize, $fontPath);
        }
    }

    /**
     * Get the default OG image URL, generating if needed.
     */
    public function getDefaultUrl(): ?string
    {
        $filename = 'og-images/default.png';
        $fullPath = $this->getPath($filename);

        if (Storage::disk($this->disk)->exists($fullPath)) {
            return Storage::disk($this->disk)->url($fullPath);
        }

        return $this->generateDefault();
    }
}
