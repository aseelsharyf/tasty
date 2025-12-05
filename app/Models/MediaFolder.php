<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MediaFolder extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'name',
        'parent_id',
    ];

    /**
     * Get the parent folder.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'parent_id');
    }

    /**
     * Get the child folders.
     */
    public function children(): HasMany
    {
        return $this->hasMany(MediaFolder::class, 'parent_id');
    }

    /**
     * Get all descendant folders recursively.
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    /**
     * Get the media items in this folder.
     */
    public function items(): HasMany
    {
        return $this->hasMany(MediaItem::class, 'folder_id');
    }

    /**
     * Get the full path of the folder (e.g., "Parent / Child / Current").
     */
    public function getPathAttribute(): string
    {
        $path = [$this->name];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }

        return implode(' / ', $path);
    }

    /**
     * Get the folder tree starting from root folders.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, MediaFolder>
     */
    public static function tree(): \Illuminate\Database\Eloquent\Collection
    {
        return static::whereNull('parent_id')
            ->with('descendants')
            ->orderBy('name')
            ->get();
    }
}
