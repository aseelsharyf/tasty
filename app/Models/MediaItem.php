<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

class MediaItem extends Model implements HasMedia
{
    use HasFactory, HasTranslations, HasUuid, InteractsWithMedia, SoftDeletes;

    public const TYPE_IMAGE = 'image';

    public const TYPE_VIDEO_LOCAL = 'video_local';

    public const TYPE_VIDEO_EMBED = 'video_embed';

    public const ROLE_PHOTOGRAPHER = 'photographer';

    public const ROLE_VIDEOGRAPHER = 'videographer';

    public const ROLE_ILLUSTRATOR = 'illustrator';

    public const ROLE_OTHER = 'other';

    // Default media categories
    public const CATEGORY_MEDIA = 'media';

    public const CATEGORY_SPONSORS = 'sponsors';

    public const CATEGORY_AVATARS = 'avatars';

    public const CATEGORY_CLIENTS = 'clients';

    public const CATEGORY_PRODUCTS = 'products';

    public const CATEGORY_OTHERS = 'others';

    /** @var array<string> */
    public array $translatable = ['title', 'caption', 'description', 'alt_text'];

    protected $fillable = [
        'type',
        'category',
        'embed_url',
        'embed_provider',
        'embed_video_id',
        'embed_thumbnail_url',
        'title',
        'caption',
        'description',
        'alt_text',
        'credit_user_id',
        'credit_name',
        'credit_url',
        'credit_role',
        'width',
        'height',
        'blurhash',
        'file_size',
        'mime_type',
        'duration',
        'folder_id',
        'uploaded_by',
    ];

    protected $appends = [
        'url',
        'original_url',
        'thumbnail_url',
        'is_image',
        'is_video',
        'credit_display',
    ];

    /**
     * Register media conversions based on settings.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        // Optimized WebP version - main display image
        // This creates an optimized WebP version for web display
        $this->addMediaConversion('optimized')
            ->format('webp')
            ->quality(85)
            ->optimize()
            ->performOnCollections('default')
            ->nonQueued(); // Generate immediately so it's available right away

        // Thumbnail in WebP format
        $this->addMediaConversion('thumb')
            ->width(400)
            ->height(300)
            ->format('webp')
            ->quality(80)
            ->optimize()
            ->performOnCollections('default')
            ->nonQueued();

        // Large optimized version for full-screen/hero images
        $this->addMediaConversion('large')
            ->width(1920)
            ->height(1080)
            ->format('webp')
            ->quality(85)
            ->optimize()
            ->fit('max', 1920, 1080) // Maintain aspect ratio, max dimensions
            ->performOnCollections('default');

        // Medium size for article content
        $this->addMediaConversion('medium')
            ->width(800)
            ->height(600)
            ->format('webp')
            ->quality(85)
            ->optimize()
            ->fit('max', 800, 600)
            ->performOnCollections('default');

        // Register crop preset conversions
        $presets = Setting::get('media.crop_presets', self::getDefaultCropPresets());

        foreach ($presets as $preset) {
            $this->addMediaConversion($preset['name'])
                ->width($preset['width'])
                ->height($preset['height'])
                ->format('webp')
                ->quality(85)
                ->optimize()
                ->performOnCollections('default');
        }
    }

    /**
     * Register media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('default')
            ->singleFile()
            ->acceptsMimeTypes([
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
                'image/avif',
                'image/svg+xml',
                'video/mp4',
                'video/quicktime',
                'video/webm',
            ]);
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * Get the credit user (if linked to CMS user).
     */
    public function creditUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'credit_user_id');
    }

    /**
     * Get the folder this item belongs to.
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'folder_id');
    }

    /**
     * Get the user who uploaded this item.
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the tags for this media item.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * Get the crop versions for this media item.
     */
    public function crops(): HasMany
    {
        return $this->hasMany(MediaItemCrop::class);
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * Scope to filter by type.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get only images.
     */
    public function scopeImages(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_IMAGE);
    }

    /**
     * Scope to get only videos (local and embed).
     */
    public function scopeVideos(Builder $query): Builder
    {
        return $query->whereIn('type', [self::TYPE_VIDEO_LOCAL, self::TYPE_VIDEO_EMBED]);
    }

    /**
     * Scope to filter by folder.
     */
    public function scopeInFolder(Builder $query, ?int $folderId): Builder
    {
        if ($folderId === null) {
            return $query->whereNull('folder_id');
        }

        return $query->where('folder_id', $folderId);
    }

    /**
     * Scope to filter by category.
     */
    public function scopeInCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    // =========================================================================
    // Accessors
    // =========================================================================

    /**
     * Get the main URL of the media item.
     * Returns the optimized WebP version if available, otherwise the original.
     */
    public function getUrlAttribute(): ?string
    {
        if ($this->type === self::TYPE_VIDEO_EMBED) {
            return $this->embed_thumbnail_url;
        }

        // Prefer the optimized WebP version for images
        if ($this->is_image) {
            $optimizedUrl = $this->getFirstMediaUrl('default', 'optimized');
            if ($optimizedUrl) {
                return $optimizedUrl;
            }
        }

        return $this->getFirstMediaUrl('default');
    }

    /**
     * Get the original (non-optimized) URL.
     * Useful when you need the original file (e.g., for downloads).
     */
    public function getOriginalUrlAttribute(): ?string
    {
        if ($this->type === self::TYPE_VIDEO_EMBED) {
            return $this->embed_thumbnail_url;
        }

        return $this->getFirstMediaUrl('default');
    }

    /**
     * Get the thumbnail URL.
     * Returns the WebP thumb version if available.
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        if ($this->type === self::TYPE_VIDEO_EMBED) {
            return $this->embed_thumbnail_url;
        }

        // Try thumb conversion first, then fall back to original
        return $this->getFirstMediaUrl('default', 'thumb') ?: $this->getFirstMediaUrl('default');
    }

    /**
     * Get a specific conversion URL.
     */
    public function getConversionUrl(string $conversion): ?string
    {
        return $this->getFirstMediaUrl('default', $conversion) ?: $this->getFirstMediaUrl('default');
    }

    /**
     * Check if this is an image.
     */
    public function getIsImageAttribute(): bool
    {
        return $this->type === self::TYPE_IMAGE;
    }

    /**
     * Check if this is a video (local or embed).
     */
    public function getIsVideoAttribute(): bool
    {
        return in_array($this->type, [self::TYPE_VIDEO_LOCAL, self::TYPE_VIDEO_EMBED]);
    }

    /**
     * Get unified credit display info.
     *
     * @return array{name: string|null, url: string|null, role: string|null, is_user: bool}|null
     */
    public function getCreditDisplayAttribute(): ?array
    {
        // If linked to a user, use user info
        if ($this->credit_user_id && $this->creditUser) {
            return [
                'name' => $this->creditUser->name,
                'url' => null,
                'role' => $this->credit_role,
                'is_user' => true,
                'user_id' => $this->credit_user_id,
            ];
        }

        // If free text credit
        if ($this->credit_name) {
            return [
                'name' => $this->credit_name,
                'url' => $this->credit_url,
                'role' => $this->credit_role,
                'is_user' => false,
                'user_id' => null,
            ];
        }

        return null;
    }

    // =========================================================================
    // Static Helpers
    // =========================================================================

    /**
     * Get default crop presets.
     *
     * @return array<int, array{name: string, label: string, width: int, height: int}>
     */
    public static function getDefaultCropPresets(): array
    {
        return [
            ['name' => 'thumbnail', 'label' => 'Thumbnail', 'width' => 300, 'height' => 200],
            ['name' => 'medium', 'label' => 'Medium', 'width' => 800, 'height' => 600],
            ['name' => 'large', 'label' => 'Large', 'width' => 1920, 'height' => 1080],
            ['name' => 'social', 'label' => 'Social (OG)', 'width' => 1200, 'height' => 630],
        ];
    }

    /**
     * Get available credit roles.
     *
     * @return array<string, string>
     */
    public static function getCreditRoles(): array
    {
        return [
            self::ROLE_PHOTOGRAPHER => 'Photographer',
            self::ROLE_VIDEOGRAPHER => 'Videographer',
            self::ROLE_ILLUSTRATOR => 'Illustrator',
            self::ROLE_OTHER => 'Other',
        ];
    }

    /**
     * Get default media categories.
     *
     * @return array<int, array{slug: string, label: string}>
     */
    public static function getDefaultCategories(): array
    {
        return [
            ['slug' => self::CATEGORY_MEDIA, 'label' => 'Media'],
            ['slug' => self::CATEGORY_SPONSORS, 'label' => 'Sponsors'],
            ['slug' => self::CATEGORY_AVATARS, 'label' => 'Avatars'],
            ['slug' => self::CATEGORY_CLIENTS, 'label' => 'Clients'],
            ['slug' => self::CATEGORY_PRODUCTS, 'label' => 'Products'],
            ['slug' => self::CATEGORY_OTHERS, 'label' => 'Others'],
        ];
    }

    /**
     * Get media categories from settings with fallback to defaults.
     *
     * @return array<int, array{slug: string, label: string}>
     */
    public static function getCategories(): array
    {
        return Setting::get('media.categories', self::getDefaultCategories());
    }

    /**
     * Parse category from filename based on configured categories.
     * Returns 'media' as default if no match found.
     */
    public static function parseCategoryFromFilename(string $filename): string
    {
        $filename = strtolower($filename);
        $categories = self::getCategories();

        foreach ($categories as $category) {
            $slug = strtolower($category['slug']);
            // Check if filename contains the category slug
            if (str_contains($filename, $slug)) {
                return $category['slug'];
            }
        }

        // Default to 'media' category
        return self::CATEGORY_MEDIA;
    }

    /**
     * Parse a video embed URL to extract provider and video ID.
     *
     * @return array{provider: string, video_id: string, thumbnail_url: string}|null
     */
    public static function parseEmbedUrl(string $url): ?array
    {
        // YouTube patterns
        $youtubePatterns = [
            '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/',
        ];

        foreach ($youtubePatterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                $videoId = $matches[1];

                return [
                    'provider' => 'youtube',
                    'video_id' => $videoId,
                    'thumbnail_url' => "https://img.youtube.com/vi/{$videoId}/maxresdefault.jpg",
                ];
            }
        }

        // Vimeo patterns
        $vimeoPatterns = [
            '/vimeo\.com\/(\d+)/',
            '/player\.vimeo\.com\/video\/(\d+)/',
        ];

        foreach ($vimeoPatterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                $videoId = $matches[1];

                // Note: Vimeo thumbnails require API call, using placeholder
                return [
                    'provider' => 'vimeo',
                    'video_id' => $videoId,
                    'thumbnail_url' => "https://vumbnail.com/{$videoId}.jpg",
                ];
            }
        }

        return null;
    }

    /**
     * Get embed iframe URL for video embeds.
     */
    public function getEmbedIframeUrl(): ?string
    {
        if ($this->type !== self::TYPE_VIDEO_EMBED || ! $this->embed_video_id) {
            return null;
        }

        return match ($this->embed_provider) {
            'youtube' => "https://www.youtube.com/embed/{$this->embed_video_id}",
            'vimeo' => "https://player.vimeo.com/video/{$this->embed_video_id}",
            default => null,
        };
    }
}
