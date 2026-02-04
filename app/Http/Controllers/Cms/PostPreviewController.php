<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ContentVersion;
use App\Models\Language;
use App\Models\Post;
use App\Models\Setting;
use App\Models\Sponsor;
use App\Models\Tag;
use App\Models\User;
use App\Services\PostTemplateRegistry;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostPreviewController extends Controller
{
    /**
     * Render a live preview of the post using Blade templates.
     * Accepts POST data so it can preview unsaved changes.
     */
    public function render(Request $request): View
    {
        // Content may come as JSON string from form submission
        $contentInput = $request->input('content');
        if (is_string($contentInput)) {
            $request->merge(['content' => json_decode($contentInput, true) ?: []]);
        }

        // Custom fields may come as JSON string from form submission
        $customFieldsInput = $request->input('custom_fields');
        if (is_string($customFieldsInput)) {
            $request->merge(['custom_fields' => json_decode($customFieldsInput, true) ?: []]);
        }

        // Featured image anchor may come as JSON string
        $anchorInput = $request->input('featured_image_anchor');
        if (is_string($anchorInput)) {
            $request->merge(['featured_image_anchor' => json_decode($anchorInput, true) ?: null]);
        }

        // Cover video may come as JSON string from form submission
        $coverVideoInput = $request->input('cover_video');
        if (is_string($coverVideoInput) && ! empty($coverVideoInput)) {
            $request->merge(['cover_video' => json_decode($coverVideoInput, true) ?: null]);
        }

        $validated = $request->validate([
            'title' => ['required', 'string'],
            'kicker' => ['nullable', 'string'],
            'subtitle' => ['nullable', 'string'],
            'excerpt' => ['nullable', 'string'],
            'content' => ['nullable', 'array'],
            'template' => ['nullable', 'string'],
            'language_code' => ['nullable', 'string'],
            'featured_image_url' => ['nullable', 'string'],
            'featured_image_anchor' => ['nullable', 'array'],
            'author' => ['nullable', 'array'],
            'show_author' => ['nullable'],
            'category' => ['nullable', 'string'],
            'tags' => ['nullable', 'string'],
            'post_type' => ['nullable', 'string'],
            'custom_fields' => ['nullable', 'array'],
            'cover_video' => ['nullable', 'array'],
            'sponsor_id' => ['nullable', 'integer'],
        ]);

        $template = $validated['template'] ?? 'default';
        $languageCode = $validated['language_code'] ?? 'en';

        // Validate template exists
        if (! PostTemplateRegistry::exists($template)) {
            $template = PostTemplateRegistry::getDefault();
        }

        // Get template config
        $templateConfig = PostTemplateRegistry::get($template);

        // Determine RTL
        $language = Language::where('code', $languageCode)->first();
        $isRtl = $language?->isRtl() ?? false;

        // Calculate reading time
        $content = $validated['content'] ?? [];
        $wordCount = $this->countWords($content);
        $readingTime = $this->calculateReadingTime($wordCount);

        // Parse show_author (can come as string "true"/"false" or "1"/"0" from form)
        $showAuthor = filter_var($validated['show_author'] ?? true, FILTER_VALIDATE_BOOLEAN);

        // Parse tags from comma-separated string
        $tagsString = $validated['tags'] ?? '';
        $tags = array_filter(array_map('trim', explode(',', $tagsString)));

        // Get post type config
        $postTypeSlug = $validated['post_type'] ?? 'article';
        $postTypes = Setting::getPostTypes();
        $postTypeConfig = collect($postTypes)->firstWhere('slug', $postTypeSlug);
        $customFields = $validated['custom_fields'] ?? [];

        // Build a temporary Post model instance (without saving)
        $post = new Post([
            'title' => $validated['title'],
            'kicker' => $validated['kicker'] ?? null,
            'subtitle' => $validated['subtitle'] ?? null,
            'excerpt' => $validated['excerpt'] ?? null,
            'language_code' => $languageCode,
            'show_author' => $showAuthor,
            'post_type' => $postTypeSlug,
            'custom_fields' => $customFields,
            'content' => $content,
            'template' => $template,
            'featured_image_anchor' => $validated['featured_image_anchor'] ?? ['x' => 50, 'y' => 0],
        ]);

        // Override featured_image_url accessor by setting the attribute directly
        if ($validated['featured_image_url'] ?? null) {
            $post->setAttribute('featured_image_url_override', $validated['featured_image_url']);
        }

        // Set up author relationship if provided
        $authorData = $validated['author'] ?? null;
        if ($authorData && isset($authorData['id'])) {
            $post->setRelation('author', User::find($authorData['id']));
        } else {
            $post->setRelation('author', null);
        }

        // Set up categories relationship
        $categorySlug = $validated['category'] ?? null;
        if ($categorySlug) {
            $category = Category::where('slug', $categorySlug)->first();
            $post->setRelation('categories', $category ? collect([$category]) : collect());
        } else {
            $post->setRelation('categories', collect());
        }

        // Set up tags relationship from comma-separated string
        if (! empty($tags)) {
            // Tag name is a translatable JSON field, so we need to search within the JSON
            // or fall back to slug matching
            $tagModels = Tag::where(function ($query) use ($tags) {
                $query->whereIn('slug', $tags);
                // Also search in name JSON field (search all locales)
                foreach ($tags as $tag) {
                    $query->orWhereRaw('name::text ILIKE ?', ['%"'.addslashes($tag).'"%']);
                }
            })->get();
            // Create temporary tag objects for tags that don't exist yet
            foreach ($tags as $tagName) {
                $found = $tagModels->first(function ($t) use ($tagName) {
                    return $t->slug === $tagName || $t->getTranslation('name', 'en', false) === $tagName;
                });
                if (! $found) {
                    $tagModels->push(new Tag(['name' => $tagName, 'slug' => \Illuminate\Support\Str::slug($tagName)]));
                }
            }
            $post->setRelation('tags', $tagModels);
        } else {
            $post->setRelation('tags', collect());
        }

        // Set featuredMedia relationship to null (no media item for preview of unsaved data)
        $post->setRelation('featuredMedia', null);

        // Set coverVideo relationship from preview data
        $coverVideoData = $validated['cover_video'] ?? null;
        if ($coverVideoData && isset($coverVideoData['id'])) {
            // Try to load the actual MediaItem if it exists
            $coverVideo = \App\Models\MediaItem::find($coverVideoData['id']);
            $post->setRelation('coverVideo', $coverVideo);
        } else {
            $post->setRelation('coverVideo', null);
        }

        // Set sponsor relationship from preview data
        $sponsorId = $validated['sponsor_id'] ?? null;
        if ($sponsorId) {
            $sponsor = Sponsor::with('featuredMedia')->find($sponsorId);
            $post->setRelation('sponsor', $sponsor);
        } else {
            $post->setRelation('sponsor', null);
        }

        return view('templates.posts.preview', [
            'post' => $post,
            'template' => $template,
            'templateConfig' => $templateConfig,
            'isRtl' => $isRtl,
            'readingTime' => $readingTime,
            'wordCount' => $wordCount,
            'showAuthor' => $showAuthor,
            'postTypeConfig' => $postTypeConfig,
            'isPreview' => true,
        ]);
    }

    /**
     * Count words in EditorJS content blocks.
     */
    private function countWords(array $content): int
    {
        $text = '';
        $blocks = $content['blocks'] ?? [];

        foreach ($blocks as $block) {
            $data = $block['data'] ?? [];

            if (isset($data['text'])) {
                $text .= ' '.strip_tags($data['text']);
            }

            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    if (is_string($item)) {
                        $text .= ' '.strip_tags($item);
                    } elseif (is_array($item) && isset($item['content'])) {
                        $text .= ' '.strip_tags($item['content']);
                    }
                }
            }

            if (isset($data['caption'])) {
                $text .= ' '.strip_tags($data['caption']);
            }
        }

        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);

        return count($words);
    }

    /**
     * Calculate reading time from word count.
     */
    private function calculateReadingTime(int $wordCount): string
    {
        $minutes = (int) ceil($wordCount / 200);

        if ($minutes < 1) {
            return 'Less than 1 min read';
        }

        return $minutes.' min read';
    }

    /**
     * Show preview for a saved post, optionally at a specific version.
     */
    public function show(Request $request, string $language, Post $post): View
    {
        // Load relationships
        $post->load(['author', 'categories', 'tags', 'featuredMedia', 'coverVideo', 'sponsor.featuredMedia']);

        // Optionally load a specific version and apply snapshot to model
        $versionUuid = $request->query('version');

        if ($versionUuid) {
            $version = ContentVersion::where('uuid', $versionUuid)
                ->where('versionable_type', Post::class)
                ->where('versionable_id', $post->id)
                ->first();

            if ($version && $version->content_snapshot) {
                $snapshot = $version->content_snapshot;
                // Apply version snapshot to the model (without saving)
                $post->title = $snapshot['title'] ?? $post->title;
                $post->kicker = $snapshot['kicker'] ?? $post->kicker;
                $post->subtitle = $snapshot['subtitle'] ?? $post->subtitle;
                $post->excerpt = $snapshot['excerpt'] ?? $post->excerpt;
                $post->content = $snapshot['content'] ?? $post->content;
                $post->template = $snapshot['template'] ?? $post->template;
                $post->meta_title = $snapshot['meta_title'] ?? $post->meta_title;
                $post->meta_description = $snapshot['meta_description'] ?? $post->meta_description;
                $post->show_author = $snapshot['show_author'] ?? $post->show_author;
                $post->featured_image_anchor = $snapshot['featured_image_anchor'] ?? $post->featured_image_anchor;
                $post->custom_fields = $snapshot['custom_fields'] ?? $post->custom_fields;

                // Update featured media if different in snapshot
                if (isset($snapshot['featured_media_id']) && $snapshot['featured_media_id'] !== $post->featured_media_id) {
                    $post->featured_media_id = $snapshot['featured_media_id'];
                    // Reload the featuredMedia relationship
                    $post->load('featuredMedia');
                }

                // Update cover video if different in snapshot
                if (isset($snapshot['cover_video_id']) && $snapshot['cover_video_id'] !== $post->cover_video_id) {
                    $post->cover_video_id = $snapshot['cover_video_id'];
                    // Reload the coverVideo relationship
                    $post->load('coverVideo');
                }
            }
        }

        $template = $post->template ?? 'default';

        // Validate template exists
        if (! PostTemplateRegistry::exists($template)) {
            $template = PostTemplateRegistry::getDefault();
        }

        $templateConfig = PostTemplateRegistry::get($template);

        // Determine RTL
        $languageModel = Language::where('code', $post->language_code)->first();
        $isRtl = $languageModel?->isRtl() ?? false;

        // Calculate reading time
        $content = $post->content ?? [];
        $wordCount = $this->countWords($content);
        $readingTime = $this->calculateReadingTime($wordCount);

        // Get post type config
        $postTypeSlug = $post->post_type ?? 'article';
        $postTypes = Setting::getPostTypes();
        $postTypeConfig = collect($postTypes)->firstWhere('slug', $postTypeSlug);

        return view('templates.posts.preview', [
            'post' => $post,
            'template' => $template,
            'templateConfig' => $templateConfig,
            'isRtl' => $isRtl,
            'readingTime' => $readingTime,
            'wordCount' => $wordCount,
            'showAuthor' => $post->show_author ?? true,
            'postTypeConfig' => $postTypeConfig,
            'isPreview' => true,
        ]);
    }
}
