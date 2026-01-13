<?php

namespace App\Services\Layouts;

/**
 * Tracks which posts have been used across sections on a page.
 *
 * This service is request-scoped, meaning a fresh instance is created
 * for each HTTP request. It allows sections to mark posts as "used"
 * so that subsequent sections can exclude them from their queries,
 * preventing the same post from appearing multiple times on a page.
 */
class UsedPostTracker
{
    /** @var array<int, bool> */
    protected array $usedIds = [];

    /**
     * Mark one or more posts as used.
     *
     * @param  int|array<int>  $ids
     */
    public function markUsed(int|array $ids): void
    {
        $ids = is_array($ids) ? $ids : [$ids];

        foreach ($ids as $id) {
            if ($id !== null && $id > 0) {
                $this->usedIds[$id] = true;
            }
        }
    }

    /**
     * Get all used post IDs.
     *
     * @return array<int>
     */
    public function getUsedIds(): array
    {
        return array_keys($this->usedIds);
    }

    /**
     * Check if a post has been used.
     */
    public function isUsed(int $id): bool
    {
        return isset($this->usedIds[$id]);
    }

    /**
     * Get count of used posts.
     */
    public function count(): int
    {
        return count($this->usedIds);
    }

    /**
     * Clear all tracked posts (useful for testing).
     */
    public function clear(): void
    {
        $this->usedIds = [];
    }
}
