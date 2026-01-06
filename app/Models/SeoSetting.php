<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class SeoSetting extends Model
{
    use HasFactory, HasTranslations;

    /**
     * @var array<int, string>
     */
    public array $translatable = [
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_title',
        'og_description',
        'twitter_title',
        'twitter_description',
    ];

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'route_name',
        'page_type',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_title',
        'og_description',
        'og_image',
        'og_type',
        'twitter_card',
        'twitter_title',
        'twitter_description',
        'twitter_image',
        'canonical_url',
        'robots',
        'json_ld',
        'is_active',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta_keywords' => 'array',
            'json_ld' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get SEO setting by route name.
     */
    public static function findByRoute(string $routeName): ?self
    {
        return static::where('route_name', $routeName)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get all static page SEO settings.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, SeoSetting>
     */
    public static function getStaticPages(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('page_type', 'static')
            ->where('is_active', true)
            ->get();
    }
}
