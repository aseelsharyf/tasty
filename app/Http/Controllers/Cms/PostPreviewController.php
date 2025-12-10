<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Post;
use App\Models\PostVersion;
use App\Models\Setting;
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

        $validated = $request->validate([
            'title' => ['required', 'string'],
            'subtitle' => ['nullable', 'string'],
            'excerpt' => ['nullable', 'string'],
            'content' => ['nullable', 'array'],
            'template' => ['nullable', 'string'],
            'language_code' => ['nullable', 'string'],
            'featured_image_url' => ['nullable', 'string'],
            'author' => ['nullable', 'array'],
            'show_author' => ['nullable'],
            'category' => ['nullable', 'string'],
            'tags' => ['nullable', 'string'],
            'post_type' => ['nullable', 'string'],
            'custom_fields' => ['nullable', 'array'],
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

        // Build post data array
        $post = [
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'] ?? null,
            'excerpt' => $validated['excerpt'] ?? null,
            'featured_image_url' => $validated['featured_image_url'] ?? null,
            'language_code' => $languageCode,
            'author' => $validated['author'] ?? null,
            'show_author' => $showAuthor,
            'category' => $validated['category'] ?? null,
            'tags' => $tags,
            'post_type' => $postTypeSlug,
            'custom_fields' => $customFields,
        ];

        return view('templates.posts.preview', [
            'post' => $post,
            'content' => $content,
            'template' => $template,
            'templateConfig' => $templateConfig,
            'isRtl' => $isRtl,
            'readingTime' => $readingTime,
            'wordCount' => $wordCount,
            'showAuthor' => $showAuthor,
            'postTypeConfig' => $postTypeConfig,
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
        // Optionally load a specific version
        $versionUuid = $request->query('version');
        $content = $post->content ?? [];
        $template = $post->template ?? 'default';

        if ($versionUuid) {
            $version = PostVersion::where('uuid', $versionUuid)
                ->where('post_id', $post->id)
                ->first();

            if ($version && $version->content_snapshot) {
                $snapshot = $version->content_snapshot;
                $content = $snapshot['content'] ?? $content;
                $template = $snapshot['template'] ?? $template;
            }
        }

        // Validate template exists
        if (! PostTemplateRegistry::exists($template)) {
            $template = PostTemplateRegistry::getDefault();
        }

        $templateConfig = PostTemplateRegistry::get($template);

        // Determine RTL
        $languageModel = Language::where('code', $post->language_code)->first();
        $isRtl = $languageModel?->isRtl() ?? false;

        // Calculate reading time
        $wordCount = $this->countWords($content);
        $readingTime = $this->calculateReadingTime($wordCount);

        // Get show_author from version snapshot or post
        $showAuthor = $versionUuid && isset($snapshot['show_author'])
            ? $snapshot['show_author']
            : ($post->show_author ?? true);

        // Load categories and tags
        $post->load(['categories', 'tags']);

        // Get post type config
        $postTypeSlug = $post->post_type ?? 'article';
        $postTypes = Setting::getPostTypes();
        $postTypeConfig = collect($postTypes)->firstWhere('slug', $postTypeSlug);

        // Build post data array
        $postData = [
            'title' => $post->title,
            'subtitle' => $post->subtitle,
            'excerpt' => $post->excerpt,
            'featured_image_url' => $post->featured_image_url,
            'language_code' => $post->language_code,
            'author' => $post->author ? ['name' => $post->author->name] : null,
            'show_author' => $showAuthor,
            'category' => $post->categories->first()?->name,
            'tags' => $post->tags->pluck('name')->toArray(),
            'post_type' => $postTypeSlug,
            'custom_fields' => $post->custom_fields ?? [],
        ];

        return view('templates.posts.preview', [
            'post' => $postData,
            'content' => $content,
            'template' => $template,
            'templateConfig' => $templateConfig,
            'isRtl' => $isRtl,
            'readingTime' => $readingTime,
            'wordCount' => $wordCount,
            'showAuthor' => $showAuthor,
            'postTypeConfig' => $postTypeConfig,
        ]);
    }
}
