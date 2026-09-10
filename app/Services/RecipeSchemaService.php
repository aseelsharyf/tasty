<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Support\Str;

class RecipeSchemaService
{
    /** @return array<string, mixed> */
    public function build(Post $post): array
    {
        $fields = $post->custom_fields ?? [];
        $values = [];
        $ingredients = [];

        foreach ($fields['ingredients'] ?? [] as $section) {
            if (empty($section['section'])) {
                continue;
            }

            foreach ($section['items'] ?? [] as $item) {
                $text = $this->plainText($item);

                if ($text !== '') {
                    $ingredients[] = $text;
                }
            }
        }

        if ($ingredients !== []) {
            $values['recipeIngredient'] = $ingredients;
        }

        $content = $post->content ?? [];
        $instructions = $this->instructions($content['blocks'] ?? $content);

        if ($instructions !== []) {
            $values['recipeInstructions'] = $instructions;
        }

        $prepTime = $this->minutes($fields['prep_time'] ?? null);
        $cookTime = $this->minutes($fields['cook_time'] ?? null);

        if ($prepTime !== null) {
            $values['prepTime'] = 'PT'.$prepTime.'M';
        }

        if ($cookTime !== null) {
            $values['cookTime'] = 'PT'.$cookTime.'M';
        }

        if ($prepTime !== null && $cookTime !== null) {
            $values['totalTime'] = 'PT'.($prepTime + $cookTime).'M';
        }

        $servings = $this->plainText($fields['servings'] ?? null);

        if ($servings !== '' && (! is_numeric($servings) || (float) $servings > 0)) {
            $values['recipeYield'] = $servings;
        }

        if ($post->language_code) {
            $values['inLanguage'] = $post->language_code;
        }

        return $values;
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     * @return list<array{'@type': string, text: string}>
     */
    protected function instructions(array $blocks): array
    {
        $steps = [];

        foreach ($blocks as $block) {
            $data = $block['data'] ?? [];

            if (($block['type'] ?? null) === 'paragraph') {
                $text = $this->plainText($data['text'] ?? null);

                if ($text !== '') {
                    $steps[] = ['@type' => 'HowToStep', 'text' => $text];
                }
            } elseif (in_array($block['type'] ?? null, ['list', 'checklist'], true)) {
                $steps = array_merge($steps, $this->listInstructions($data['items'] ?? []));
            } elseif (($block['type'] ?? null) === 'collapsible') {
                $steps = array_merge($steps, $this->instructions($data['content']['blocks'] ?? []));
            }
        }

        return $steps;
    }

    /**
     * @param  array<int, string|array<string, mixed>>  $items
     * @return list<array{'@type': string, text: string}>
     */
    protected function listInstructions(array $items): array
    {
        $steps = [];

        foreach ($items as $item) {
            $text = $this->plainText(is_array($item) ? ($item['content'] ?? $item['text'] ?? null) : $item);

            if ($text !== '') {
                $steps[] = ['@type' => 'HowToStep', 'text' => $text];
            }

            if (is_array($item) && ! empty($item['items'])) {
                $steps = array_merge($steps, $this->listInstructions($item['items']));
            }
        }

        return $steps;
    }

    protected function plainText(mixed $value): string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return '';
        }

        $text = preg_replace('/<br\s*\/?\s*>/i', ' ', (string) $value);

        return Str::squish(strip_tags(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    protected function minutes(mixed $value): ?int
    {
        if ((! is_int($value) && ! is_string($value)) || ! preg_match('/^\d+$/', (string) $value)) {
            return null;
        }

        return (int) $value;
    }
}
