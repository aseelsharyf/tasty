<?php

namespace Database\Seeders;

use App\Models\MediaItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class MediaSeeder extends Seeder
{
    /**
     * Seed sample media items with placeholder images.
     */
    public function run(): void
    {
        $photographer = User::whereHas('roles', function ($query) {
            $query->where('name', 'Photographer');
        })->first() ?? User::first();

        $this->command->info('Downloading sample images from picsum.photos...');

        // Create 20 sample images
        for ($i = 1; $i <= 20; $i++) {
            $this->createMediaItem($i, $photographer);
        }

        $this->command->info('Created 20 media items with sample images.');
    }

    private function createMediaItem(int $index, ?User $photographer): void
    {
        // Use picsum.photos for random food-related placeholder images
        // Adding seed to get consistent images across runs
        $seed = 100 + $index;
        $width = 1200;
        $height = 800;

        $imageUrl = "https://picsum.photos/seed/{$seed}/{$width}/{$height}";

        try {
            $response = Http::timeout(30)->get($imageUrl);

            if (! $response->successful()) {
                $this->command->warn("Failed to download image {$index}");

                return;
            }

            $imageContent = $response->body();
            $filename = "sample-image-{$index}.jpg";

            // Store temporarily
            $tempPath = "temp/{$filename}";
            Storage::disk('local')->put($tempPath, $imageContent);
            $fullTempPath = Storage::disk('local')->path($tempPath);

            // Create MediaItem
            $mediaItem = MediaItem::create([
                'type' => MediaItem::TYPE_IMAGE,
                'title' => ['en' => "Sample Image {$index}", 'dv' => "ސާމްޕަލް ފޮޓੋ {$index}"],
                'alt_text' => ['en' => "Sample food image {$index}", 'dv' => "ސާމްޕަލް ކާނާގެ ފޮޓੋ {$index}"],
                'caption' => ['en' => 'Photo for demonstration purposes', 'dv' => 'ޑެމੋ ބޭނުމަށް ފੋٹو'],
                'credit_user_id' => $photographer?->id,
                'credit_role' => MediaItem::ROLE_PHOTOGRAPHER,
                'width' => $width,
                'height' => $height,
                'uploaded_by' => $photographer?->id,
            ]);

            // Attach the image using Spatie Media Library
            $mediaItem->addMedia($fullTempPath)
                ->usingFileName($filename)
                ->toMediaCollection('default');

            // Clean up temp file
            Storage::disk('local')->delete($tempPath);

            $this->command->info("Created media item {$index}: {$mediaItem->title}");

        } catch (\Exception $e) {
            $this->command->error("Error creating media item {$index}: {$e->getMessage()}");
        }
    }
}
