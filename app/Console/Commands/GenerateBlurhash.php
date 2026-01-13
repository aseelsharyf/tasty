<?php

namespace App\Console\Commands;

use App\Models\MediaItem;
use App\Services\BlurHashService;
use Illuminate\Console\Command;

class GenerateBlurhash extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:generate-blurhash
                            {--id= : Generate blurhash for a specific media item ID}
                            {--all : Generate blurhash for all images}
                            {--missing : Only generate for images without blurhash}
                            {--force : Regenerate even if blurhash already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate blurhash placeholders for media images';

    public function __construct(
        private BlurHashService $blurHashService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('id')) {
            return $this->generateSingle((int) $this->option('id'));
        }

        if ($this->option('all') || $this->option('missing')) {
            return $this->generateAll();
        }

        $this->error('Please specify --id=<id>, --all, or --missing');

        return self::FAILURE;
    }

    private function generateSingle(int $id): int
    {
        $mediaItem = MediaItem::find($id);

        if (! $mediaItem) {
            $this->error("Media item with ID {$id} not found.");

            return self::FAILURE;
        }

        if (! $mediaItem->is_image) {
            $this->error('This media item is not an image.');

            return self::FAILURE;
        }

        $media = $mediaItem->getFirstMedia('default');
        if (! $media) {
            $this->error('No media file found for this item.');

            return self::FAILURE;
        }

        $this->info("Generating blurhash for media item #{$id}...");

        // Use URL for S3/remote storage, fallback to path for local
        $imagePath = $media->getUrl() ?: $media->getPath();
        $blurhash = $this->blurHashService->encode($imagePath);

        if ($blurhash) {
            $mediaItem->update(['blurhash' => $blurhash]);
            $this->info("Blurhash generated: {$blurhash}");

            return self::SUCCESS;
        }

        $this->error('Failed to generate blurhash.');

        return self::FAILURE;
    }

    private function generateAll(): int
    {
        $query = MediaItem::query()
            ->where('type', MediaItem::TYPE_IMAGE)
            ->whereHas('media');

        // Only missing blurhash
        if ($this->option('missing') && ! $this->option('force')) {
            $query->whereNull('blurhash');
        }

        $total = $query->count();

        if ($total === 0) {
            $this->info('No images found that need blurhash generation.');

            return self::SUCCESS;
        }

        $this->info("Found {$total} images to process.");

        if (! $this->option('force') && ! $this->confirm('Continue?', true)) {
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $generated = 0;
        $failed = 0;
        $skipped = 0;

        $query->chunk(50, function ($items) use ($bar, &$generated, &$failed, &$skipped) {
            foreach ($items as $mediaItem) {
                // Skip if already has blurhash and not forcing
                if ($mediaItem->blurhash && ! $this->option('force')) {
                    $skipped++;
                    $bar->advance();

                    continue;
                }

                $media = $mediaItem->getFirstMedia('default');

                if (! $media) {
                    $failed++;
                    $bar->advance();

                    continue;
                }

                try {
                    // Use URL for S3/remote storage, fallback to path for local
                    $imagePath = $media->getUrl() ?: $media->getPath();
                    $blurhash = $this->blurHashService->encode($imagePath);

                    if ($blurhash) {
                        $mediaItem->update(['blurhash' => $blurhash]);
                        $generated++;
                    } else {
                        $failed++;
                    }
                } catch (\Exception $e) {
                    $failed++;
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->info('Completed!');
        $this->table(
            ['Status', 'Count'],
            [
                ['Generated', $generated],
                ['Failed', $failed],
                ['Skipped', $skipped],
            ]
        );

        return self::SUCCESS;
    }
}
