<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Exception\CommonMarkException;

class Page extends Model
{
    /** @use HasFactory<\Database\Factories\PageFactory> */
    use HasFactory, HasUuid, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const EDITOR_MODE_CODE = 'code';

    public const EDITOR_MODE_MARKDOWN = 'markdown';

    protected $fillable = [
        'uuid',
        'language_code',
        'title',
        'slug',
        'content',
        'layout',
        'status',
        'is_blade',
        'editor_mode',
        'author_id',
        'meta_title',
        'meta_description',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_blade' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Scope to get only published pages.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope to get pages by status.
     */
    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Get the author of the page.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Get the language of the page.
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'language_code', 'code');
    }

    /**
     * Check if the page is published.
     */
    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && $this->published_at !== null
            && $this->published_at->isPast();
    }

    /**
     * Check if this page uses Blade rendering.
     */
    public function usesBlade(): bool
    {
        return $this->is_blade;
    }

    /**
     * Check if this page uses Markdown editor.
     */
    public function usesMarkdown(): bool
    {
        return $this->editor_mode === self::EDITOR_MODE_MARKDOWN;
    }

    /**
     * Render the page content.
     * Handles both Blade templates and Markdown content.
     */
    public function renderContent(): string
    {
        if (! $this->content) {
            return '';
        }

        // Handle markdown content
        if ($this->usesMarkdown()) {
            try {
                $converter = new CommonMarkConverter([
                    'html_input' => 'allow',
                    'allow_unsafe_links' => false,
                ]);

                return $converter->convert($this->content)->getContent();
            } catch (CommonMarkException $e) {
                // If markdown parsing fails, return content as-is
                return $this->content;
            }
        }

        // Handle Blade content
        if ($this->usesBlade()) {
            return Blade::render($this->content, [
                'page' => $this,
            ]);
        }

        return $this->content;
    }

    /**
     * Get the meta title for SEO.
     */
    public function getMetaTitleAttribute(?string $value): string
    {
        return $value ?? $this->title;
    }

    /**
     * Find a published page by slug with caching.
     */
    public static function findBySlug(string $slug): ?self
    {
        return Cache::remember(
            "page.slug.{$slug}",
            now()->addHours(1),
            fn () => static::published()->where('slug', $slug)->first()
        );
    }

    /**
     * Clear the page cache.
     */
    public static function clearCache(string $slug): void
    {
        Cache::forget("page.slug.{$slug}");
    }

    /**
     * Get available statuses.
     *
     * @return array<string, string>
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PUBLISHED => 'Published',
        ];
    }

    /**
     * Get available layouts.
     *
     * @return array<string, string>
     */
    public static function getLayouts(): array
    {
        return [
            'default' => 'Default',
        ];
    }
}
