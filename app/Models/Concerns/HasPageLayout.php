<?php

namespace App\Models\Concerns;

use App\Models\PageLayout;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasPageLayout
{
    /**
     * @return MorphOne<PageLayout, $this>
     */
    public function pageLayout(): MorphOne
    {
        return $this->morphOne(PageLayout::class, 'layoutable');
    }

    /**
     * Get the layout configuration for this entity.
     *
     * @return array<string, mixed>|null
     */
    public function getLayoutConfiguration(): ?array
    {
        return $this->pageLayout?->configuration;
    }

    /**
     * Check if this entity has a custom layout enabled.
     */
    public function hasCustomLayout(): bool
    {
        if ($this->pageLayout === null) {
            return false;
        }

        $config = $this->pageLayout->configuration;

        // Check if layout is enabled (defaults to true for backward compatibility)
        $isEnabled = $config['enabled'] ?? true;

        return $isEnabled && ! empty($config['sections']);
    }
}
