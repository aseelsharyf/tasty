<?php

namespace App\Services;

use kornrunner\Blurhash\Blurhash;

class BlurHashService
{
    /**
     * Default components for encoding.
     * Higher values = more detail but longer string.
     * 4x3 is a good balance for most images.
     */
    private const COMPONENTS_X = 4;

    private const COMPONENTS_Y = 3;

    /**
     * Max dimension for the image used to generate blurhash.
     * Smaller = faster generation.
     */
    private const MAX_SAMPLE_SIZE = 64;

    /**
     * Generate a blurhash from an image file path or URL.
     */
    public function encode(string $imagePath): ?string
    {
        // Handle URLs (for S3/remote storage)
        if (str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')) {
            $contents = @file_get_contents($imagePath);
            if ($contents === false) {
                return null;
            }

            return $this->encodeFromString($contents);
        }

        if (! file_exists($imagePath)) {
            return null;
        }

        try {
            // Get image info
            $imageInfo = @getimagesize($imagePath);
            if (! $imageInfo) {
                return null;
            }

            $mimeType = $imageInfo['mime'];

            // Create image resource based on type
            $image = match ($mimeType) {
                'image/jpeg', 'image/jpg' => imagecreatefromjpeg($imagePath),
                'image/png' => imagecreatefrompng($imagePath),
                'image/gif' => imagecreatefromgif($imagePath),
                'image/webp' => imagecreatefromwebp($imagePath),
                default => null,
            };

            if (! $image) {
                return null;
            }

            // Get original dimensions
            $width = imagesx($image);
            $height = imagesy($image);

            // Resize for faster processing
            $sampleWidth = min($width, self::MAX_SAMPLE_SIZE);
            $sampleHeight = (int) ($height * ($sampleWidth / $width));

            $resized = imagecreatetruecolor($sampleWidth, $sampleHeight);

            // Handle transparency for PNG
            if ($mimeType === 'image/png') {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
            }

            imagecopyresampled($resized, $image, 0, 0, 0, 0, $sampleWidth, $sampleHeight, $width, $height);

            // Extract pixels
            $pixels = [];
            for ($y = 0; $y < $sampleHeight; $y++) {
                $row = [];
                for ($x = 0; $x < $sampleWidth; $x++) {
                    $rgb = imagecolorat($resized, $x, $y);
                    $row[] = [
                        ($rgb >> 16) & 0xFF, // Red
                        ($rgb >> 8) & 0xFF,  // Green
                        $rgb & 0xFF,         // Blue
                    ];
                }
                $pixels[] = $row;
            }

            // Clean up
            imagedestroy($image);
            imagedestroy($resized);

            // Encode to blurhash
            return Blurhash::encode($pixels, self::COMPONENTS_X, self::COMPONENTS_Y);
        } catch (\Exception $e) {
            report($e);

            return null;
        }
    }

    /**
     * Generate a blurhash from image contents (binary string).
     */
    public function encodeFromString(string $imageContents): ?string
    {
        try {
            $image = imagecreatefromstring($imageContents);
            if (! $image) {
                return null;
            }

            // Get dimensions
            $width = imagesx($image);
            $height = imagesy($image);

            // Resize for faster processing
            $sampleWidth = min($width, self::MAX_SAMPLE_SIZE);
            $sampleHeight = (int) ($height * ($sampleWidth / $width));

            $resized = imagecreatetruecolor($sampleWidth, $sampleHeight);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $sampleWidth, $sampleHeight, $width, $height);

            // Extract pixels
            $pixels = [];
            for ($y = 0; $y < $sampleHeight; $y++) {
                $row = [];
                for ($x = 0; $x < $sampleWidth; $x++) {
                    $rgb = imagecolorat($resized, $x, $y);
                    $row[] = [
                        ($rgb >> 16) & 0xFF,
                        ($rgb >> 8) & 0xFF,
                        $rgb & 0xFF,
                    ];
                }
                $pixels[] = $row;
            }

            // Clean up
            imagedestroy($image);
            imagedestroy($resized);

            return Blurhash::encode($pixels, self::COMPONENTS_X, self::COMPONENTS_Y);
        } catch (\Exception $e) {
            report($e);

            return null;
        }
    }

    /**
     * Generate a CSS-compatible data URI for the blurhash placeholder.
     * This creates a tiny canvas rendering of the blurhash.
     */
    public function toDataUri(string $blurhash, int $width = 32, int $height = 32): ?string
    {
        try {
            $pixels = Blurhash::decode($blurhash, $width, $height);

            $image = imagecreatetruecolor($width, $height);

            for ($y = 0; $y < $height; $y++) {
                for ($x = 0; $x < $width; $x++) {
                    $pixel = $pixels[$y][$x];
                    $color = imagecolorallocate($image, $pixel[0], $pixel[1], $pixel[2]);
                    imagesetpixel($image, $x, $y, $color);
                }
            }

            ob_start();
            imagepng($image);
            $contents = ob_get_clean();
            imagedestroy($image);

            return 'data:image/png;base64,'.base64_encode($contents);
        } catch (\Exception $e) {
            return null;
        }
    }
}
