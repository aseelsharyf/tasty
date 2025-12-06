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

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public const STATUS_PENDING = 'pending';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_SCHEDULED = 'scheduled';

    public const TYPE_ARTICLE = 'article';

    public const TYPE_RECIPE = 'recipe';

    protected $fillable = [
        'author_id',
        'language_code',
        'title',
        'subtitle',
        'slug',
        'excerpt',
        'content',
        'post_type',
        'status',
        'workflow_status',
        'published_at',
        'scheduled_at',
        'featured_image_id',
        'featured_media_id',
        'active_version_id',
        'draft_version_id',
        'custom_fields',
        'meta_title',
        'meta_description',
        'allow_comments',
    ];

    protected $appends = [
        'featured_image_url',
        'featured_image_thumb',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'custom_fields' => 'array',
            'published_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'allow_comments' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Post $post) {
            if (empty($post->slug)) {
                $post->slug = static::generateUniqueSlug($post->title);
            }
        });
    }

    public static function generateUniqueSlug(string $title): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug ?: 'post';
        $counter = 1;

        while (static::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
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

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
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

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isRecipe(): bool
    {
        return $this->post_type === self::TYPE_RECIPE;
    }

    public function publish(): void
    {
        $this->update([
            'status' => self::STATUS_PUBLISHED,
            'published_at' => now(),
            'scheduled_at' => null,
        ]);
    }

    public function unpublish(): void
    {
        $this->update([
            'status' => self::STATUS_DRAFT,
            'published_at' => null,
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

    public function submitForReview(): void
    {
        $this->update([
            'status' => self::STATUS_PENDING,
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
            'subtitle',
            'excerpt',
            'content',
            'meta_title',
            'meta_description',
            'featured_media_id',
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
