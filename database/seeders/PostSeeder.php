<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Language;
use App\Models\MediaItem;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        // Get existing writers/editors or create them
        $authors = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['Admin', 'Editor', 'Writer']);
        })->get();

        if ($authors->isEmpty()) {
            $authors = User::take(3)->get();
        }

        if ($authors->isEmpty()) {
            $this->command->warn('No users found. Skipping PostSeeder.');

            return;
        }

        // Get languages
        $languages = Language::active()->get();
        if ($languages->isEmpty()) {
            $this->command->warn('No active languages found. Skipping PostSeeder.');

            return;
        }

        // Get categories and tags
        $categories = Category::all();
        $tags = Tag::all();

        // Get media items for featured images and content
        $mediaItems = MediaItem::images()->get();
        if ($mediaItems->isEmpty()) {
            $this->command->warn('No media items found. Run MediaSeeder first for images.');
        }

        foreach ($languages as $language) {
            // Create 10 published articles per language
            Post::factory()
                ->count(10)
                ->article()
                ->withLanguage($language->code)
                ->create([
                    'author_id' => fn () => $authors->random()->id,
                    'workflow_status' => 'draft',
                    'content' => $this->getArticleContent($mediaItems),
                    'featured_media_id' => fn () => $mediaItems->isNotEmpty() ? $mediaItems->random()->id : null,
                ])
                ->each(function ($post) use ($categories, $tags) {
                    // Create initial version
                    $version = $post->createVersion(null, 'Initial version');

                    // Attach categories and tags
                    if ($categories->isNotEmpty()) {
                        $post->categories()->attach(
                            $categories->random(min(rand(1, 3), $categories->count()))->pluck('id')
                        );
                    }
                    if ($tags->isNotEmpty()) {
                        $post->tags()->attach(
                            $tags->random(min(rand(2, 5), $tags->count()))->pluck('id')
                        );
                    }

                    // Transition to Published
                    $version->transitionTo('review', 'Ready for review', $post->author_id);
                    $version->transitionTo('approved', 'Approved for publication', $post->author_id);
                    $version->transitionTo('published', 'Published', $post->author_id);

                    // Activate the version
                    $version->activate();
                    $post->publish();
                });

            // Create 3 draft posts per language
            Post::factory()
                ->count(3)
                ->draft()
                ->article()
                ->withLanguage($language->code)
                ->create([
                    'author_id' => fn () => $authors->random()->id,
                    'workflow_status' => 'draft',
                    'content' => $this->getArticleContent($mediaItems),
                    'featured_media_id' => fn () => $mediaItems->isNotEmpty() ? $mediaItems->random()->id : null,
                ])
                ->each(function ($post) {
                    $post->createVersion(null, 'Initial draft');
                });

            // Create 2 pending review posts per language
            Post::factory()
                ->count(2)
                ->pending()
                ->article()
                ->withLanguage($language->code)
                ->create([
                    'author_id' => fn () => $authors->random()->id,
                    'workflow_status' => 'draft',
                    'content' => $this->getArticleContent($mediaItems),
                    'featured_media_id' => fn () => $mediaItems->isNotEmpty() ? $mediaItems->random()->id : null,
                ])
                ->each(function ($post) {
                    $version = $post->createVersion(null, 'Submitted for review');
                    $version->transitionTo('review', 'Submitted for review', $post->author_id);
                    $post->submitForReview();
                });

            // Create 2 approved posts per language
            Post::factory()
                ->count(2)
                ->pending()
                ->article()
                ->withLanguage($language->code)
                ->create([
                    'author_id' => fn () => $authors->random()->id,
                    'workflow_status' => 'draft',
                    'content' => $this->getArticleContent($mediaItems),
                    'featured_media_id' => fn () => $mediaItems->isNotEmpty() ? $mediaItems->random()->id : null,
                ])
                ->each(function ($post) {
                    $version = $post->createVersion(null, 'Initial version');
                    $version->transitionTo('review', 'Ready for review', $post->author_id);
                    $version->transitionTo('approved', 'Approved for publication', $post->author_id);
                    $post->update(['workflow_status' => 'approved']);
                });

            // Create 2 published recipes per language
            Post::factory()
                ->count(2)
                ->recipe()
                ->withLanguage($language->code)
                ->create([
                    'author_id' => fn () => $authors->random()->id,
                    'workflow_status' => 'draft',
                    'content' => $this->getRecipeContent($mediaItems),
                    'featured_media_id' => fn () => $mediaItems->isNotEmpty() ? $mediaItems->random()->id : null,
                ])
                ->each(function ($post) {
                    $version = $post->createVersion(null, 'Initial version');

                    $version->transitionTo('review', 'Ready for review', $post->author_id);
                    $version->transitionTo('approved', 'Approved', $post->author_id);
                    $version->transitionTo('published', 'Published', $post->author_id);

                    $version->activate();
                    $post->publish();
                });

            $this->command->info("Created posts for language: {$language->name}");
        }
    }

    /**
     * Get standard article content with images in EditorJS format.
     *
     * @param  \Illuminate\Support\Collection<int, MediaItem>  $mediaItems
     */
    private function getArticleContent($mediaItems): array
    {
        $blocks = [];

        $blocks[] = [
            'id' => $this->generateBlockId(),
            'type' => 'paragraph',
            'data' => [
                'text' => 'The culinary landscape of the Maldives is a fascinating tapestry woven from centuries of maritime trade, cultural exchange, and island ingenuity. From the bustling fish markets of Malé to the intimate family kitchens on remote atolls, food tells the story of a nation shaped by the sea.',
            ],
        ];

        $blocks[] = [
            'id' => $this->generateBlockId(),
            'type' => 'paragraph',
            'data' => [
                'text' => 'At the heart of Maldivian cuisine lies the humble tuna, known locally as kandu mas. This versatile fish appears in countless preparations, from the beloved mas huni served at breakfast to the rich, aromatic curries that grace dinner tables across the islands.',
            ],
        ];

        // Add a media block if available
        if ($mediaItems->isNotEmpty()) {
            $image1 = $mediaItems->random();
            $blocks[] = $this->createMediaBlock($image1, 'Traditional Maldivian cuisine showcases the bounty of the Indian Ocean');
        }

        $blocks[] = [
            'id' => $this->generateBlockId(),
            'type' => 'header',
            'data' => [
                'text' => 'A Heritage of Flavors',
                'level' => 2,
            ],
        ];

        $blocks[] = [
            'id' => $this->generateBlockId(),
            'type' => 'paragraph',
            'data' => [
                'text' => 'The influence of neighboring South Asian cuisines is unmistakable in Maldivian cooking. Coconut, curry leaves, and an aromatic blend of spices form the foundation of many dishes. Yet, the Maldivian palate has developed its own distinct identity, favoring subtle heat and allowing the natural flavors of fresh seafood to shine.',
            ],
        ];

        // Add a quote block with author photo (if media available)
        $quoteAuthorPhoto = $mediaItems->isNotEmpty() ? $mediaItems->random() : null;
        $blocks[] = $this->createQuoteBlock(
            'Food is our common ground, a universal experience that connects us all—especially here in the Maldives, where every meal tells a story of the sea.',
            'Chef Ibrahim Mohamed',
            'Executive Chef, Ocean Restaurant',
            $quoteAuthorPhoto
        );

        $blocks[] = [
            'id' => $this->generateBlockId(),
            'type' => 'paragraph',
            'data' => [
                'text' => 'One cannot discuss Maldivian food without mentioning the significance of roshi, the unleavened flatbread that accompanies virtually every meal. Made from flour, water, and a touch of oil, this simple bread becomes extraordinary when paired with spicy fish curry or the sweet condensed milk treat known as dhon riha.',
            ],
        ];

        // Add a multi-image grid block if enough images available
        if ($mediaItems->count() >= 3) {
            $blocks[] = $this->createMediaGridBlock($mediaItems, rand(2, 3));
        } elseif ($mediaItems->count() > 1) {
            $image2 = $mediaItems->random();
            $blocks[] = $this->createMediaBlock($image2, 'Fresh ingredients are the cornerstone of island cooking');
        }

        $blocks[] = [
            'id' => $this->generateBlockId(),
            'type' => 'header',
            'data' => [
                'text' => 'Modern Interpretations',
                'level' => 2,
            ],
        ];

        $blocks[] = [
            'id' => $this->generateBlockId(),
            'type' => 'paragraph',
            'data' => [
                'text' => 'Today\'s Maldivian chefs are reimagining traditional recipes for contemporary palates. In the kitchens of luxury resorts and innovative local restaurants, age-old techniques meet modern presentation. The result is a cuisine that honors its roots while embracing new possibilities.',
            ],
        ];

        // Add another quote (without photo to show both variants)
        $blocks[] = $this->createQuoteBlock(
            'The secret to great Maldivian cooking is simple: respect the ingredients. Let the fish speak for itself, and the spices will follow.',
            'Chef Aminath Hassan',
            'Culinary Director'
        );

        $blocks[] = [
            'id' => $this->generateBlockId(),
            'type' => 'paragraph',
            'data' => [
                'text' => 'Whether you\'re sampling street food in Hulhumalé or enjoying a multi-course tasting menu overlooking the lagoon, the essence of Maldivian hospitality shines through. Food here is more than sustenance—it\'s an expression of community, tradition, and the islands\' deep connection to the surrounding waters.',
            ],
        ];

        return [
            'time' => now()->timestamp * 1000,
            'blocks' => $blocks,
            'version' => '2.28.0',
        ];
    }

    /**
     * Create a media block for EditorJS (single image).
     */
    private function createMediaBlock(MediaItem $media, ?string $caption = null): array
    {
        return [
            'id' => $this->generateBlockId(),
            'type' => 'media',
            'data' => [
                'items' => [
                    $this->createMediaItem($media, $caption),
                ],
                'layout' => 'single',
                'gridColumns' => 3,
                'gap' => 'md',
            ],
        ];
    }

    /**
     * Create a media block with multiple images (grid layout).
     *
     * @param  \Illuminate\Support\Collection<int, MediaItem>  $mediaItems
     */
    private function createMediaGridBlock($mediaItems, int $count = 2): array
    {
        $items = $mediaItems->random(min($count, $mediaItems->count()))
            ->map(fn ($media) => $this->createMediaItem($media))
            ->toArray();

        return [
            'id' => $this->generateBlockId(),
            'type' => 'media',
            'data' => [
                'items' => $items,
                'layout' => 'grid',
                'gridColumns' => count($items),
                'gap' => 'md',
            ],
        ];
    }

    /**
     * Create a single media item array.
     */
    private function createMediaItem(MediaItem $media, ?string $caption = null): array
    {
        return [
            'id' => $media->id,
            'uuid' => $media->uuid,
            'url' => $media->url,
            'thumbnail_url' => $media->thumbnail_url,
            'title' => $media->getTranslation('title', 'en'),
            'alt_text' => $media->getTranslation('alt_text', 'en'),
            'caption' => $caption ?? $media->getTranslation('caption', 'en'),
            'credit_display' => $media->credit_display,
            'is_image' => $media->is_image,
            'is_video' => $media->is_video,
        ];
    }

    /**
     * Create a quote block for EditorJS with optional author photo.
     */
    private function createQuoteBlock(
        string $text,
        string $authorName,
        ?string $authorTitle = null,
        ?MediaItem $authorPhoto = null,
        string $alignment = 'left'
    ): array {
        $data = [
            'text' => $text,
            'caption' => $authorName,
            'alignment' => $alignment,
            'author' => [
                'name' => $authorName,
                'title' => $authorTitle,
                'photo' => $authorPhoto ? $this->createMediaItem($authorPhoto) : null,
            ],
        ];

        return [
            'id' => $this->generateBlockId(),
            'type' => 'quote',
            'data' => $data,
        ];
    }

    /**
     * Generate a unique block ID for EditorJS.
     */
    private function generateBlockId(): string
    {
        return fake()->unique()->regexify('[a-zA-Z0-9]{10}');
    }

    /**
     * Get standard recipe content with images in EditorJS format.
     *
     * @param  \Illuminate\Support\Collection<int, MediaItem>  $mediaItems
     */
    private function getRecipeContent($mediaItems): array
    {
        $blocks = [];

        $blocks[] = [
            'id' => $this->generateBlockId(),
            'type' => 'paragraph',
            'data' => [
                'text' => 'This beloved Maldivian dish brings together the freshest catch of the day with aromatic spices and creamy coconut milk. Perfect for a family dinner or special occasion, this recipe has been passed down through generations.',
            ],
        ];

        // Add featured media if available
        if ($mediaItems->isNotEmpty()) {
            $image = $mediaItems->random();
            $blocks[] = $this->createMediaBlock($image, 'The finished dish, ready to serve');
        }

        $blocks[] = [
            'id' => $this->generateBlockId(),
            'type' => 'header',
            'data' => [
                'text' => 'Ingredients',
                'level' => 2,
            ],
        ];

        $blocks[] = [
            'id' => $this->generateBlockId(),
            'type' => 'list',
            'data' => [
                'style' => 'unordered',
                'meta' => [],
                'items' => [
                    ['content' => '500g fresh tuna, cut into chunks', 'meta' => [], 'items' => []],
                    ['content' => '1 cup coconut milk', 'meta' => [], 'items' => []],
                    ['content' => '2 onions, finely sliced', 'meta' => [], 'items' => []],
                    ['content' => '4 cloves garlic, minced', 'meta' => [], 'items' => []],
                    ['content' => '1 inch ginger, grated', 'meta' => [], 'items' => []],
                    ['content' => '2 green chilies, sliced', 'meta' => [], 'items' => []],
                    ['content' => '10 curry leaves', 'meta' => [], 'items' => []],
                    ['content' => '1 tsp turmeric powder', 'meta' => [], 'items' => []],
                    ['content' => '1 tsp chili powder', 'meta' => [], 'items' => []],
                    ['content' => '1 tsp cumin powder', 'meta' => [], 'items' => []],
                    ['content' => 'Salt to taste', 'meta' => [], 'items' => []],
                    ['content' => '2 tbsp coconut oil', 'meta' => [], 'items' => []],
                ],
            ],
        ];

        $blocks[] = [
            'id' => $this->generateBlockId(),
            'type' => 'header',
            'data' => [
                'text' => 'Instructions',
                'level' => 2,
            ],
        ];

        $blocks[] = [
            'id' => $this->generateBlockId(),
            'type' => 'list',
            'data' => [
                'style' => 'ordered',
                'meta' => [],
                'items' => [
                    ['content' => 'Heat coconut oil in a deep pan over medium heat. Add curry leaves and let them splutter.', 'meta' => [], 'items' => []],
                    ['content' => 'Add sliced onions and sauté until golden brown, about 8-10 minutes.', 'meta' => [], 'items' => []],
                    ['content' => 'Add garlic, ginger, and green chilies. Cook for another 2 minutes until fragrant.', 'meta' => [], 'items' => []],
                    ['content' => 'Add turmeric, chili powder, and cumin. Stir well to combine with the aromatics.', 'meta' => [], 'items' => []],
                    ['content' => 'Gently add the tuna chunks, coating them with the spice mixture. Cook for 3-4 minutes.', 'meta' => [], 'items' => []],
                    ['content' => 'Pour in the coconut milk and bring to a gentle simmer. Do not boil vigorously.', 'meta' => [], 'items' => []],
                    ['content' => 'Cover and cook for 15-20 minutes until the fish is cooked through and the curry has thickened.', 'meta' => [], 'items' => []],
                    ['content' => 'Season with salt to taste. Serve hot with steamed rice or roshi.', 'meta' => [], 'items' => []],
                ],
            ],
        ];

        // Add step media if available
        if ($mediaItems->count() > 1) {
            $stepImage = $mediaItems->random();
            $blocks[] = $this->createMediaBlock($stepImage, 'The curry should have a rich, creamy consistency');
        }

        $blocks[] = [
            'id' => $this->generateBlockId(),
            'type' => 'header',
            'data' => [
                'text' => 'Chef\'s Tips',
                'level' => 2,
            ],
        ];

        // Add a quote tip (with author photo if available)
        $tipAuthorPhoto = $mediaItems->isNotEmpty() ? $mediaItems->random() : null;
        $blocks[] = $this->createQuoteBlock(
            'The key is to never let the coconut milk boil—a gentle simmer keeps the fish tender and the sauce silky smooth.',
            'Dhaitha',
            'Grandmother\'s Wisdom',
            $tipAuthorPhoto
        );

        $blocks[] = [
            'id' => $this->generateBlockId(),
            'type' => 'paragraph',
            'data' => [
                'text' => 'For the best results, use the freshest tuna you can find. The quality of the fish makes all the difference in this simple yet flavorful dish. If fresh tuna is unavailable, you can substitute with any firm white fish, though the flavor will differ slightly.',
            ],
        ];

        return [
            'time' => now()->timestamp * 1000,
            'blocks' => $blocks,
            'version' => '2.28.0',
        ];
    }
}
