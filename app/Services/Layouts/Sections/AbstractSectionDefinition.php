<?php

namespace App\Services\Layouts\Sections;

abstract class AbstractSectionDefinition
{
    /**
     * Get the section type identifier.
     */
    abstract public function type(): string;

    /**
     * Get the section display name.
     */
    abstract public function name(): string;

    /**
     * Get the section description.
     */
    abstract public function description(): string;

    /**
     * Get the section icon (Lucide icon class).
     */
    abstract public function icon(): string;

    /**
     * Get the default number of content slots this section has.
     */
    abstract public function slotCount(): int;

    /**
     * Get the minimum number of slots allowed.
     */
    public function minSlots(): int
    {
        return $this->slotCount();
    }

    /**
     * Get the maximum number of slots allowed (0 = unlimited).
     */
    public function maxSlots(): int
    {
        return 0; // Unlimited by default
    }

    /**
     * Get the configuration schema for this section.
     *
     * @return array<string, array{type: string, label: string, default: mixed, options?: array<string>, placeholder?: string}>
     */
    abstract public function configSchema(): array;

    /**
     * Get the supported data source actions.
     *
     * @return array<string>
     */
    abstract public function supportedActions(): array;

    /**
     * Get the slot schema for static content.
     * Each slot can have static content fields when mode is 'static'.
     *
     * @return array<string, array{type: string, label: string, default?: mixed, placeholder?: string}>
     */
    public function slotSchema(): array
    {
        // Default post-like slot schema
        return [
            'title' => [
                'type' => 'text',
                'label' => 'Title',
                'placeholder' => 'Enter title',
            ],
            'description' => [
                'type' => 'textarea',
                'label' => 'Description',
                'placeholder' => 'Enter description',
            ],
            'image' => [
                'type' => 'media',
                'label' => 'Image',
                'placeholder' => 'Select or enter image URL',
            ],
            'url' => [
                'type' => 'text',
                'label' => 'Link URL',
                'placeholder' => 'Enter URL',
            ],
            'category' => [
                'type' => 'text',
                'label' => 'Category',
                'placeholder' => 'Enter category name',
            ],
            'author' => [
                'type' => 'text',
                'label' => 'Author',
                'placeholder' => 'Enter author name',
            ],
            'date' => [
                'type' => 'text',
                'label' => 'Date',
                'placeholder' => 'Enter date',
            ],
        ];
    }

    /**
     * Get the slot labels based on position.
     *
     * @return array<int, string>
     */
    public function slotLabels(): array
    {
        $labels = [];
        for ($i = 0; $i < $this->slotCount(); $i++) {
            $labels[$i] = 'Slot '.($i + 1);
        }

        return $labels;
    }

    /**
     * Get the preview schema for the wireframe display.
     * Defines the visual structure as data for the frontend to render.
     *
     * @return array{
     *   layout: string,
     *   areas: array<array{
     *     id: string,
     *     label: string,
     *     width?: string,
     *     height?: string,
     *     slotIndex?: int,
     *     showPlay?: bool
     *   }>
     * }
     */
    public function previewSchema(): array
    {
        return [
            'layout' => 'single',
            'areas' => [
                ['id' => 'main', 'label' => 'Content', 'width' => 'full', 'height' => 'full'],
            ],
        ];
    }

    /**
     * Whether this section supports dynamic data fetching.
     */
    public function supportsDynamic(): bool
    {
        return count($this->supportedActions()) > 0;
    }

    /**
     * Get the default configuration values.
     *
     * @return array<string, mixed>
     */
    public function defaultConfig(): array
    {
        $defaults = [];

        foreach ($this->configSchema() as $key => $schema) {
            $defaults[$key] = $schema['default'] ?? null;
        }

        return $defaults;
    }

    /**
     * Get the default slot configuration.
     *
     * @return array<int, array{index: int, mode: string, postId: int|null, content: array<string, mixed>}>
     */
    public function defaultSlots(): array
    {
        $slots = [];
        $slotSchema = $this->slotSchema();
        $defaultContent = [];

        // Build default content from slot schema
        foreach ($slotSchema as $key => $field) {
            $defaultContent[$key] = $field['default'] ?? '';
        }

        for ($i = 0; $i < $this->slotCount(); $i++) {
            $slots[] = [
                'index' => $i,
                'mode' => $this->supportsDynamic() ? 'dynamic' : 'static',
                'postId' => null,
                'content' => $defaultContent,
            ];
        }

        return $slots;
    }

    /**
     * Get the default data source configuration.
     *
     * @return array{action: string, params: array<string, mixed>}
     */
    public function defaultDataSource(): array
    {
        $actions = $this->supportedActions();

        return [
            'action' => $actions[0] ?? 'recent',
            'params' => [],
        ];
    }

    /**
     * Convert the section definition to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type(),
            'name' => $this->name(),
            'description' => $this->description(),
            'icon' => $this->icon(),
            'slotCount' => $this->slotCount(),
            'minSlots' => $this->minSlots(),
            'maxSlots' => $this->maxSlots(),
            'configSchema' => $this->configSchema(),
            'slotSchema' => $this->slotSchema(),
            'slotLabels' => $this->slotLabels(),
            'supportedActions' => $this->supportedActions(),
            'supportsDynamic' => $this->supportsDynamic(),
            'defaultConfig' => $this->defaultConfig(),
            'defaultSlots' => $this->defaultSlots(),
            'defaultDataSource' => $this->defaultDataSource(),
            'previewSchema' => $this->previewSchema(),
        ];
    }
}
