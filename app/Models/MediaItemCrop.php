<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaItemCrop extends Model implements HasMedia
{
    use HasFactory, HasUuid, InteractsWithMedia;

    protected $fillable = [
        'media_item_id',
        'preset_name',
        'preset_label',
        'label',
        'crop_x',
        'crop_y',
        'crop_width',
        'crop_height',
        'output_width',
        'output_height',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'crop_x' => 'decimal:4',
            'crop_y' => 'decimal:4',
            'crop_width' => 'decimal:4',
            'crop_height' => 'decimal:4',
            'output_width' => 'integer',
            'output_height' => 'integer',
        ];
    }

    protected $appends = ['url', 'thumbnail_url'];

    // =========================================================================
    // Spatie Media Collections
    // =========================================================================

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('crop')
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(150)
            ->height(150)
            ->performOnCollections('crop');
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function mediaItem(): BelongsTo
    {
        return $this->belongsTo(MediaItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // =========================================================================
    // Accessors
    // =========================================================================

    public function getUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('crop');
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('crop', 'thumb') ?: $this->url;
    }

    /**
     * Get display label (user label or preset label).
     */
    public function getDisplayLabelAttribute(): string
    {
        return $this->label ?: $this->preset_label;
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Get crop coordinates as pixel values based on original image dimensions.
     *
     * @return array{x: int, y: int, width: int, height: int}
     */
    public function getCropPixels(int $originalWidth, int $originalHeight): array
    {
        return [
            'x' => (int) round($originalWidth * $this->crop_x / 100),
            'y' => (int) round($originalHeight * $this->crop_y / 100),
            'width' => (int) round($originalWidth * $this->crop_width / 100),
            'height' => (int) round($originalHeight * $this->crop_height / 100),
        ];
    }
}
