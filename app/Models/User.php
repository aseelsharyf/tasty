<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasMedia
{
    use HasFactory, HasRoles, HasUuid, InteractsWithMedia, Notifiable;

    public const TYPE_STAFF = 'staff';

    public const TYPE_CONTRIBUTOR = 'contributor';

    public const TYPE_USER = 'user';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'google_id',
        'avatar',
        'type',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'avatar_url',
        'url',
    ];

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (empty($user->username)) {
                $user->username = static::generateUniqueUsername($user->name);
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public static function generateUniqueUsername(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug ?: 'user';
        $counter = 1;

        while (static::where('username', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    public function canAccessCms(): bool
    {
        return $this->hasAnyRole(['Admin', 'Developer', 'Writer', 'Editor', 'Photographer']);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(100)
            ->height(100)
            ->performOnCollections('avatar');
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('avatar', 'thumb') ?: null;
    }

    public function getUrlAttribute(): ?string
    {
        if (empty($this->username)) {
            return null;
        }

        try {
            return route('author.show', $this->username);
        } catch (\Symfony\Component\Routing\Exception\RouteNotFoundException) {
            return '#';
        }
    }

    // Scopes

    public function scopeStaff(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_STAFF);
    }

    public function scopeContributors(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_CONTRIBUTOR);
    }

    public function scopeRegularUsers(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_USER);
    }

    // Helpers

    public function isStaff(): bool
    {
        return $this->type === self::TYPE_STAFF;
    }

    // Relationships

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(Badge::class)->withTimestamps();
    }

    /**
     * Get user's targets.
     */
    public function targets(): HasMany
    {
        return $this->hasMany(UserTarget::class);
    }

    /**
     * Get user's posts.
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'author_id');
    }

    /**
     * Get current target for a period type.
     */
    public function getCurrentTarget(string $periodType = UserTarget::PERIOD_MONTHLY): ?UserTarget
    {
        return $this->targets()
            ->current($periodType)
            ->first();
    }
}
