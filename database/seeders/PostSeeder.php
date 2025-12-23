<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Language;
use App\Models\MediaItem;
use App\Models\Post;
use App\Models\Sponsor;
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

        if ($categories->isEmpty()) {
            $this->command->warn('No categories found. Skipping PostSeeder.');

            return;
        }

        if ($tags->isEmpty()) {
            $this->command->warn('No tags found. Skipping PostSeeder.');

            return;
        }

        // Create sponsors if they don't exist
        $sponsors = $this->createSponsors();

        // Get media items for featured images and content
        $mediaItems = MediaItem::images()->get();
        if ($mediaItems->isEmpty()) {
            $this->command->warn('No media items found. Run MediaSeeder first for images.');
        }

        // Main navigation categories that must have posts
        $mainCategorySlugs = ['update', 'feature', 'people', 'review', 'recipe', 'pantry'];
        $mainCategories = Category::whereIn('slug', $mainCategorySlugs)->get();

        foreach ($languages as $language) {
            // Create at least one published article for each main category
            foreach ($mainCategories as $mainCategory) {
                $this->createPublishedArticleForCategory($language, $authors, $mainCategory, $tags, $sponsors, $mediaItems);
            }

            // Create additional random published articles (4 more per language)
            $this->createPublishedArticles($language, $authors, $categories, $tags, $sponsors, $mediaItems, 4);

            // Create 3 draft posts per language
            $this->createDraftPosts($language, $authors, $categories, $tags, $mediaItems, 3);

            // Create 2 posts in review per language
            $this->createPostsInReview($language, $authors, $categories, $tags, $mediaItems, 2);

            // Create 2 approved posts per language (waiting to be published)
            $this->createApprovedPosts($language, $authors, $categories, $tags, $mediaItems, 2);

            // Create 2 published recipes per language
            $this->createPublishedRecipes($language, $authors, $categories, $tags, $sponsors, $mediaItems, 2);

            $this->command->info("Created posts for language: {$language->name}");
        }
    }

    /**
     * Create sponsors for posts.
     *
     * @return \Illuminate\Support\Collection<int, Sponsor>
     */
    private function createSponsors()
    {
        $sponsorData = [
            [
                'name' => ['en' => 'Dhiraagu', 'dv' => 'ދިރާގު'],
                'url' => ['en' => 'https://www.dhiraagu.com.mv', 'dv' => 'https://www.dhiraagu.com.mv'],
                'slug' => 'dhiraagu',
            ],
            [
                'name' => ['en' => 'Bank of Maldives', 'dv' => 'ބޭންކް އޮފް މޯލްޑިވްސް'],
                'url' => ['en' => 'https://www.bankofmaldives.com.mv', 'dv' => 'https://www.bankofmaldives.com.mv'],
                'slug' => 'bank-of-maldives',
            ],
            [
                'name' => ['en' => 'Ooredoo Maldives', 'dv' => 'އޫރިދޫ މޯލްޑިވްސް'],
                'url' => ['en' => 'https://www.ooredoo.mv', 'dv' => 'https://www.ooredoo.mv'],
                'slug' => 'ooredoo-maldives',
            ],
            [
                'name' => ['en' => 'MIFCO', 'dv' => 'މިފްކޯ'],
                'url' => ['en' => 'https://www.mifco.com.mv', 'dv' => 'https://www.mifco.com.mv'],
                'slug' => 'mifco',
            ],
        ];

        foreach ($sponsorData as $data) {
            Sponsor::firstOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'url' => $data['url'],
                    'is_active' => true,
                ]
            );
        }

        return Sponsor::all();
    }

    /**
     * Create a published article for a specific category.
     */
    private function createPublishedArticleForCategory($language, $authors, Category $category, $tags, $sponsors, $mediaItems): void
    {
        $author = $authors->random();
        $featuredTag = $tags->random();
        $sponsor = fake()->boolean(30) ? $sponsors->random() : null;

        $post = Post::factory()
            ->article()
            ->withLanguage($language->code)
            ->create([
                'author_id' => $author->id,
                'featured_tag_id' => $featuredTag->id,
                'sponsor_id' => $sponsor?->id,
                'workflow_status' => 'draft',
                'status' => Post::STATUS_DRAFT,
                'content' => $this->getArticleContent($mediaItems),
                'featured_media_id' => $mediaItems->isNotEmpty() ? $mediaItems->random()->id : null,
            ]);

        // Attach to the specific category
        $post->categories()->attach($category->id);

        // Attach tags
        $additionalTags = $tags->except($featuredTag->id)->random(min(rand(2, 4), $tags->count() - 1));
        $post->tags()->attach($additionalTags->pluck('id'));
        $post->tags()->attach($featuredTag->id);

        // Create version and go through full workflow
        $version = $post->createVersion(null, 'Initial version', $author->id);
        $version->transitionTo('review', 'Submitted for editorial review', $author->id);
        $version->transitionTo('copydesk', 'Sent to copy desk for final review', $author->id);
        $version->transitionTo('approved', 'Approved for publication', $author->id);
        $version->transitionTo('published', 'Published', $author->id);

        // Activate the version and publish the post
        $version->activate();
        $post->publish();
    }

    /**
     * Create published articles with proper workflow.
     */
    private function createPublishedArticles($language, $authors, $categories, $tags, $sponsors, $mediaItems, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $author = $authors->random();
            $category = $categories->random();
            $featuredTag = $tags->random();
            // 30% chance of being sponsored
            $sponsor = fake()->boolean(30) ? $sponsors->random() : null;

            $post = Post::factory()
                ->article()
                ->withLanguage($language->code)
                ->create([
                    'author_id' => $author->id,
                    'featured_tag_id' => $featuredTag->id,
                    'sponsor_id' => $sponsor?->id,
                    'workflow_status' => 'draft',
                    'status' => Post::STATUS_DRAFT,
                    'content' => $this->getArticleContent($mediaItems),
                    'featured_media_id' => $mediaItems->isNotEmpty() ? $mediaItems->random()->id : null,
                ]);

            // Attach to category pivot table
            $post->categories()->attach($category->id);

            // Attach additional tags
            $additionalTags = $tags->except($featuredTag->id)->random(min(rand(2, 4), $tags->count() - 1));
            $post->tags()->attach($additionalTags->pluck('id'));
            $post->tags()->attach($featuredTag->id);

            // Create version and go through full workflow
            $version = $post->createVersion(null, 'Initial version', $author->id);

            // Workflow: draft → review → copydesk → approved → published
            $version->transitionTo('review', 'Submitted for editorial review', $author->id);
            $version->transitionTo('copydesk', 'Sent to copy desk for final review', $author->id);
            $version->transitionTo('approved', 'Approved for publication', $author->id);
            $version->transitionTo('published', 'Published', $author->id);

            // Activate the version and publish the post
            $version->activate();
            $post->publish();
        }
    }

    /**
     * Create draft posts.
     */
    private function createDraftPosts($language, $authors, $categories, $tags, $mediaItems, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $author = $authors->random();
            $category = $categories->random();
            $featuredTag = $tags->random();

            $post = Post::factory()
                ->draft()
                ->article()
                ->withLanguage($language->code)
                ->create([
                    'author_id' => $author->id,
                    'featured_tag_id' => $featuredTag->id,
                    'workflow_status' => 'draft',
                    'status' => Post::STATUS_DRAFT,
                    'content' => $this->getArticleContent($mediaItems),
                    'featured_media_id' => $mediaItems->isNotEmpty() ? $mediaItems->random()->id : null,
                ]);

            // Attach to category pivot table
            $post->categories()->attach($category->id);

            // Attach tags
            $post->tags()->attach($featuredTag->id);
            $additionalTags = $tags->except($featuredTag->id)->random(min(rand(1, 3), $tags->count() - 1));
            $post->tags()->attach($additionalTags->pluck('id'));

            // Create initial draft version
            $post->createVersion(null, 'Work in progress', $author->id);
        }
    }

    /**
     * Create posts that are in editorial review.
     */
    private function createPostsInReview($language, $authors, $categories, $tags, $mediaItems, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $author = $authors->random();
            $category = $categories->random();
            $featuredTag = $tags->random();

            $post = Post::factory()
                ->pending()
                ->article()
                ->withLanguage($language->code)
                ->create([
                    'author_id' => $author->id,
                    'featured_tag_id' => $featuredTag->id,
                    'workflow_status' => 'review',
                    'status' => Post::STATUS_PENDING,
                    'content' => $this->getArticleContent($mediaItems),
                    'featured_media_id' => $mediaItems->isNotEmpty() ? $mediaItems->random()->id : null,
                ]);

            // Attach to category pivot table
            $post->categories()->attach($category->id);

            // Attach tags
            $post->tags()->attach($featuredTag->id);
            $additionalTags = $tags->except($featuredTag->id)->random(min(rand(1, 3), $tags->count() - 1));
            $post->tags()->attach($additionalTags->pluck('id'));

            // Create version and transition to review
            $version = $post->createVersion(null, 'Ready for review', $author->id);
            $version->transitionTo('review', 'Submitted for editorial review', $author->id);
        }
    }

    /**
     * Create posts that are approved and waiting to be published.
     */
    private function createApprovedPosts($language, $authors, $categories, $tags, $mediaItems, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $author = $authors->random();
            $category = $categories->random();
            $featuredTag = $tags->random();

            $post = Post::factory()
                ->pending()
                ->article()
                ->withLanguage($language->code)
                ->create([
                    'author_id' => $author->id,
                    'featured_tag_id' => $featuredTag->id,
                    'workflow_status' => 'approved',
                    'status' => Post::STATUS_PENDING,
                    'content' => $this->getArticleContent($mediaItems),
                    'featured_media_id' => $mediaItems->isNotEmpty() ? $mediaItems->random()->id : null,
                ]);

            // Attach to category pivot table
            $post->categories()->attach($category->id);

            // Attach tags
            $post->tags()->attach($featuredTag->id);
            $additionalTags = $tags->except($featuredTag->id)->random(min(rand(1, 3), $tags->count() - 1));
            $post->tags()->attach($additionalTags->pluck('id'));

            // Create version and go through workflow up to approved
            $version = $post->createVersion(null, 'Initial version', $author->id);
            $version->transitionTo('review', 'Submitted for review', $author->id);
            $version->transitionTo('copydesk', 'Sent to copy desk', $author->id);
            $version->transitionTo('approved', 'Approved - ready to publish', $author->id);
        }
    }

    /**
     * Create published recipes with proper workflow.
     */
    private function createPublishedRecipes($language, $authors, $categories, $tags, $sponsors, $mediaItems, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $author = $authors->random();
            $category = $categories->random();
            $featuredTag = $tags->random();
            // 20% chance of being sponsored
            $sponsor = fake()->boolean(20) ? $sponsors->random() : null;

            $post = Post::factory()
                ->recipe()
                ->withLanguage($language->code)
                ->create([
                    'author_id' => $author->id,
                    'featured_tag_id' => $featuredTag->id,
                    'sponsor_id' => $sponsor?->id,
                    'workflow_status' => 'draft',
                    'status' => Post::STATUS_DRAFT,
                    'content' => $this->getRecipeContent($mediaItems),
                    'featured_media_id' => $mediaItems->isNotEmpty() ? $mediaItems->random()->id : null,
                ]);

            // Attach to category pivot table
            $post->categories()->attach($category->id);

            // Attach tags
            $post->tags()->attach($featuredTag->id);
            $additionalTags = $tags->except($featuredTag->id)->random(min(rand(1, 3), $tags->count() - 1));
            $post->tags()->attach($additionalTags->pluck('id'));

            // Create version and go through full workflow
            $version = $post->createVersion(null, 'Initial recipe version', $author->id);
            $version->transitionTo('review', 'Submitted for review', $author->id);
            $version->transitionTo('copydesk', 'Sent to copy desk', $author->id);
            $version->transitionTo('approved', 'Recipe approved', $author->id);
            $version->transitionTo('published', 'Published', $author->id);

            // Activate the version and publish the post
            $version->activate();
            $post->publish();
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

        // Add a media block if available (full screen width)
        if ($mediaItems->isNotEmpty()) {
            $image1 = $mediaItems->random();
            $blocks[] = $this->createMediaBlock($image1, 'Traditional Maldivian cuisine showcases the bounty of the Indian Ocean', 'fullScreen');
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

        // Quote 1: LARGE display type (side-by-side with big photo)
        $quoteAuthorPhoto1 = $mediaItems->isNotEmpty() ? $mediaItems->random() : null;
        $blocks[] = $this->createQuoteBlock(
            'Food is our common ground, a universal experience that connects us all—especially here in the Maldives, where every meal tells a story of the sea.',
            'Chef Ibrahim Mohamed',
            'Executive Chef, Ocean Restaurant',
            $quoteAuthorPhoto1,
            'left',
            'large'
        );

        $blocks[] = [
            'id' => $this->generateBlockId(),
            'type' => 'paragraph',
            'data' => [
                'text' => 'One cannot discuss Maldivian food without mentioning the significance of roshi, the unleavened flatbread that accompanies virtually every meal. Made from flour, water, and a touch of oil, this simple bread becomes extraordinary when paired with spicy fish curry or the sweet condensed milk treat known as dhon riha.',
            ],
        ];

        // Add a multi-image grid block if enough images available (full screen width)
        if ($mediaItems->count() >= 3) {
            $blocks[] = $this->createMediaGridBlock($mediaItems, rand(2, 3), 'fullScreen');
        } elseif ($mediaItems->count() > 1) {
            $image2 = $mediaItems->random();
            $blocks[] = $this->createMediaBlock($image2, 'Fresh ingredients are the cornerstone of island cooking', 'default');
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

        // Quote 2: SMALL display type (thumbnail photo on left)
        $quoteAuthorPhoto2 = $mediaItems->isNotEmpty() ? $mediaItems->random() : null;
        $blocks[] = $this->createQuoteBlock(
            'The secret to great Maldivian cooking is simple: respect the ingredients. Let the fish speak for itself, and the spices will follow.',
            'Chef Aminath Hassan',
            'Culinary Director',
            $quoteAuthorPhoto2,
            'left',
            'small'
        );

        $blocks[] = [
            'id' => $this->generateBlockId(),
            'type' => 'paragraph',
            'data' => [
                'text' => 'Whether you\'re sampling street food in Hulhumalé or enjoying a multi-course tasting menu overlooking the lagoon, the essence of Maldivian hospitality shines through. Food here is more than sustenance—it\'s an expression of community, tradition, and the islands\' deep connection to the surrounding waters.',
            ],
        ];

        // Quote 3: DEFAULT display type (text only, centered)
        $blocks[] = $this->createQuoteBlock(
            'In the Maldives, we don\'t just eat—we celebrate. Every meal is an opportunity to share stories, to connect with family, and to honor the traditions that have sustained us for generations.',
            'Fatima Ali',
            'Food Historian',
            null,
            'center',
            'default'
        );

        return [
            'time' => now()->timestamp * 1000,
            'blocks' => $blocks,
            'version' => '2.28.0',
        ];
    }

    /**
     * Create a media block for EditorJS (single image).
     */
    private function createMediaBlock(MediaItem $media, ?string $caption = null, string $displayWidth = 'default'): array
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
                'displayWidth' => $displayWidth,
            ],
        ];
    }

    /**
     * Create a media block with multiple images (grid layout).
     *
     * @param  \Illuminate\Support\Collection<int, MediaItem>  $mediaItems
     */
    private function createMediaGridBlock($mediaItems, int $count = 2, string $displayWidth = 'default'): array
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
                'displayWidth' => $displayWidth,
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
     *
     * @param  string  $displayType  'default' (text only), 'large' (big photo), 'small' (thumbnail)
     */
    private function createQuoteBlock(
        string $text,
        string $authorName,
        ?string $authorTitle = null,
        ?MediaItem $authorPhoto = null,
        string $alignment = 'left',
        string $displayType = 'default'
    ): array {
        $data = [
            'text' => $text,
            'caption' => $authorName,
            'alignment' => $alignment,
            'displayType' => $displayType,
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

        // Add a quote tip (with author photo, using small display)
        $tipAuthorPhoto = $mediaItems->isNotEmpty() ? $mediaItems->random() : null;
        $blocks[] = $this->createQuoteBlock(
            'The key is to never let the coconut milk boil—a gentle simmer keeps the fish tender and the sauce silky smooth.',
            'Dhaitha',
            'Grandmother\'s Wisdom',
            $tipAuthorPhoto,
            'left',
            'small'
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
