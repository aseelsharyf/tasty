<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use App\Models\Concerns\HasWorkflow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Post extends Model implements HasMedia
{
    use HasFactory, HasUuid, HasWorkflow, InteractsWithMedia, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_SCHEDULED = 'scheduled';

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public const TYPE_ARTICLE = 'article';

    public const TYPE_RECIPE = 'recipe';

    protected $fillable = [
        'author_id',
        'language_code',
        'title',
        'kicker',
        'subtitle',
        'slug',
        'excerpt',
        'content',
        'post_type',
        'template',
        'status',
        'workflow_status',
        'published_at',
        'scheduled_at',
        'featured_image_id',
        'featured_media_id',
        'cover_video_id',
        'featured_image_anchor',
        'featured_tag_id',
        'sponsor_id',
        'active_version_id',
        'draft_version_id',
        'custom_fields',
        'meta_title',
        'meta_description',
        'allow_comments',
        'show_author',
        'scheduled_copydesk_at',
    ];

    protected $appends = [
        'featured_image_url',
        'featured_image_thumb',
        'featured_image_blurhash',
        'url',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'custom_fields' => 'array',
            'featured_image_anchor' => 'array',
            'published_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'scheduled_copydesk_at' => 'datetime',
            'allow_comments' => 'boolean',
            'show_author' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Post $post) {
            if (empty($post->slug)) {
                $post->slug = $post->generateUniqueSlugForPost();
            }
        });

        static::updating(function (Post $post) {
            // Regenerate slug if title changed from "Untitled" pattern
            if ($post->isDirty('title') && $post->hasUntitledSlug()) {
                $post->slug = $post->generateUniqueSlugForPost();
            }
        });
    }

    /**
     * Check if the current slug is from an "Untitled" auto-generated title.
     */
    public function hasUntitledSlug(): bool
    {
        return Str::startsWith($this->slug, 'untitled-');
    }

    /**
     * Generate a unique slug for this post.
     * Uses title, with category and featured tag as prefixes for uniqueness.
     */
    public function generateUniqueSlugForPost(): string
    {
        $title = $this->title;
        $baseSlug = Str::slug($title);

        if (empty($baseSlug)) {
            $baseSlug = 'post';
        }

        // Try just the title slug first
        if ($this->isSlugAvailable($baseSlug)) {
            return $baseSlug;
        }

        // Try with category prefix
        $category = $this->categories()->first();
        if ($category) {
            $categorySlug = $category->slug.'-'.$baseSlug;
            if ($this->isSlugAvailable($categorySlug)) {
                return $categorySlug;
            }

            // Try with category + featured tag prefix
            if ($this->featured_tag_id) {
                $featuredTag = $this->featuredTag;
                if ($featuredTag) {
                    $fullSlug = $category->slug.'-'.$featuredTag->slug.'-'.$baseSlug;
                    if ($this->isSlugAvailable($fullSlug)) {
                        return $fullSlug;
                    }
                }
            }
        }

        // Fallback: append counter
        $counter = 1;
        while (! $this->isSlugAvailable("{$baseSlug}-{$counter}")) {
            $counter++;
        }

        return "{$baseSlug}-{$counter}";
    }

    /**
     * Check if a slug is available (not used by other posts).
     */
    protected function isSlugAvailable(string $slug): bool
    {
        $query = static::withTrashed()->where('slug', $slug);

        // Exclude current post if it exists
        if ($this->id) {
            $query->where('id', '!=', $this->id);
        }

        return ! $query->exists();
    }

    // Relationships

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'language_code', 'code');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function featuredMedia(): BelongsTo
    {
        return $this->belongsTo(MediaItem::class, 'featured_media_id');
    }

    public function coverVideo(): BelongsTo
    {
        return $this->belongsTo(MediaItem::class, 'cover_video_id');
    }

    public function sponsors(): BelongsToMany
    {
        return $this->belongsToMany(Sponsor::class);
    }

    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(Sponsor::class);
    }

    public function featuredTag(): BelongsTo
    {
        return $this->belongsTo(Tag::class, 'featured_tag_id');
    }

    public function approvedComments(): HasMany
    {
        return $this->comments()->approved();
    }

    public function pendingComments(): HasMany
    {
        return $this->comments()->pending();
    }

    // Scopes

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope to get posts that are in copydesk (editorial review).
     */
    public function scopeInEditorialReview(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT)
            ->where('workflow_status', 'copydesk');
    }

    /**
     * Scope alias for copydesk status.
     */
    public function scopeCopydesk(Builder $query): Builder
    {
        return $this->scopeInEditorialReview($query);
    }

    /**
     * Scope to get parked posts (approved, banked for later).
     */
    public function scopeParked(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT)
            ->where('workflow_status', 'parked');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SCHEDULED)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>', now());
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('post_type', $type);
    }

    public function scopeArticles(Builder $query): Builder
    {
        return $query->ofType(self::TYPE_ARTICLE);
    }

    public function scopeRecipes(Builder $query): Builder
    {
        return $query->ofType(self::TYPE_RECIPE);
    }

    // Media

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('featured')
            ->singleFile();

        $this->addMediaCollection('gallery');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(400)
            ->height(300)
            ->performOnCollections('featured', 'gallery');

        $this->addMediaConversion('large')
            ->width(1200)
            ->height(800)
            ->performOnCollections('featured', 'gallery');
    }

    // Accessors

    public function getFeaturedImageUrlAttribute(): ?string
    {
        // Check for preview override (unsaved data)
        if ($override = $this->getAttribute('featured_image_url_override')) {
            return $override;
        }

        // Prefer MediaItem over Spatie media collection
        if ($this->featuredMedia) {
            return $this->featuredMedia->url;
        }

        return $this->getFirstMediaUrl('featured', 'large') ?: null;
    }

    public function getFeaturedImageThumbAttribute(): ?string
    {
        // Prefer MediaItem over Spatie media collection
        if ($this->featuredMedia) {
            return $this->featuredMedia->thumbnail_url;
        }

        return $this->getFirstMediaUrl('featured', 'thumb') ?: null;
    }

    public function getFeaturedImageBlurhashAttribute(): ?string
    {
        return $this->featuredMedia?->blurhash;
    }

    public function getUrlAttribute(): string
    {
        $categorySlug = $this->categories->first()?->slug ?? 'uncategorized';

        return route('post.show', ['category' => $categorySlug, 'post' => $this->slug]);
    }

    // Helpers

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && $this->published_at
            && $this->published_at->isPast();
    }

    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED
            && $this->scheduled_at
            && $this->scheduled_at->isFuture();
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isInEditorialReview(): bool
    {
        return $this->status === self::STATUS_DRAFT
            && $this->workflow_status === 'copydesk';
    }

    public function isParked(): bool
    {
        return $this->status === self::STATUS_DRAFT
            && $this->workflow_status === 'parked';
    }

    public function isRecipe(): bool
    {
        return $this->post_type === self::TYPE_RECIPE;
    }

    public function publish(): void
    {
        $updateData = [
            'status' => self::STATUS_PUBLISHED,
            'published_at' => now(),
            'scheduled_at' => null,
        ];

        // Regenerate slug from title if it's still a placeholder
        if ($this->title && $this->shouldRegenerateSlug()) {
            $updateData['slug'] = static::generateUniqueSlug($this->title);
        }

        $this->update($updateData);
    }

    /**
     * Check if the slug should be regenerated (is a placeholder).
     */
    protected function shouldRegenerateSlug(): bool
    {
        // Regenerate if slug is empty or matches placeholder patterns
        if (empty($this->slug)) {
            return true;
        }

        // Check if slug starts with common placeholder patterns
        $placeholderPatterns = ['post', 'untitled'];
        foreach ($placeholderPatterns as $pattern) {
            if ($this->slug === $pattern || preg_match('/^'.preg_quote($pattern, '/').'-\d+$/', $this->slug)) {
                return true;
            }
        }

        return false;
    }

    public function unpublish(): void
    {
        $this->update([
            'status' => self::STATUS_DRAFT,
            'published_at' => null,
            'workflow_status' => 'copydesk',
        ]);
    }

    public function schedule(\DateTimeInterface $date): void
    {
        $this->update([
            'status' => self::STATUS_SCHEDULED,
            'scheduled_at' => $date,
            'published_at' => null,
        ]);
    }

    // Workflow overrides

    /**
     * Get the fields that should be included in version snapshots.
     *
     * @return array<string>
     */
    public function getVersionableFields(): array
    {
        return [
            'title',
            'kicker',
            'subtitle',
            'excerpt',
            'content',
            'meta_title',
            'meta_description',
            'featured_media_id',
            'featured_image_anchor',
            'custom_fields',
            'allow_comments',
        ];
    }

    /**
     * Get custom field definitions from post type configuration.
     * The field definitions are cached for performance.
     *
     * @return array<int, array{name: string, label: string, type: string, suffix?: string, options?: array}>
     */
    public function getCustomFieldDefinitions(): array
    {
        $postTypes = Setting::getPostTypes();

        foreach ($postTypes as $postType) {
            if ($postType['slug'] === $this->post_type) {
                return $postType['fields'] ?? [];
            }
        }

        return [];
    }

    /**
     * Get a specific custom field value.
     */
    public function getCustomField(string $name, mixed $default = null): mixed
    {
        return data_get($this->custom_fields, $name, $default);
    }

    /**
     * Set a specific custom field value.
     */
    public function setCustomField(string $name, mixed $value): void
    {
        $fields = $this->custom_fields ?? [];
        $fields[$name] = $value;
        $this->custom_fields = $fields;
    }
}
