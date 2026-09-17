<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Support\Str;

class RecipeSchemaService
{
    /** @var list<string> */
    private const CUISINE_TAG_SLUGS = [
        'maldivian',
        'asian',
        'indian',
        'sri-lankan',
        'thai',
        'japanese',
        'chinese',
        'italian',
        'mediterranean',
        'middle-eastern',
        'american',
        'mexican',
        'french',
        'korean',
        'vietnamese',
    ];

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
            $values['recipeInstructions'] = array_map(
                fn (string $instruction, int $index): array => [
                    '@type' => 'HowToStep',
                    'name' => $this->instructionName($instruction),
                    'text' => $instruction,
                    'url' => $post->url.'#recipe-step-'.($index + 1),
                ],
                $instructions,
                array_keys($instructions),
            );
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

        $recipeCategory = $this->recipeCategory($post, $fields);

        if ($recipeCategory !== '') {
            $values['recipeCategory'] = $recipeCategory;
        }

        $recipeCuisine = $this->recipeCuisine($post, $fields);

        if ($recipeCuisine !== '') {
            $values['recipeCuisine'] = $recipeCuisine;
        }

        $keywords = $this->keywords($post, $fields, $recipeCategory, $recipeCuisine);

        if ($keywords !== '') {
            $values['keywords'] = $keywords;
        }

        if ($post->language_code) {
            $values['inLanguage'] = $post->language_code;
        }

        return $values;
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     * @return list<string>
     */
    protected function instructions(array $blocks): array
    {
        $steps = [];

        foreach ($blocks as $block) {
            $data = $block['data'] ?? [];

            if (($block['type'] ?? null) === 'paragraph') {
                $text = $this->plainText($data['text'] ?? null);

                if ($text !== '') {
                    $steps[] = $text;
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
     * @return list<string>
     */
    protected function listInstructions(array $items): array
    {
        $steps = [];

        foreach ($items as $item) {
            $text = $this->plainText(is_array($item) ? ($item['content'] ?? $item['text'] ?? null) : $item);

            if ($text !== '') {
                $steps[] = $text;
            }

            if (is_array($item) && ! empty($item['items'])) {
                $steps = array_merge($steps, $this->listInstructions($item['items']));
            }
        }

        return $steps;
    }

    protected function instructionName(string $instruction): string
    {
        $firstSentence = Str::before($instruction, '.');

        return Str::limit($firstSentence !== '' ? $firstSentence : $instruction, 80, '');
    }

    /** @param array<string, mixed> $fields */
    protected function recipeCategory(Post $post, array $fields): string
    {
        $configuredCategory = $this->plainText($fields['recipe_category'] ?? null);

        if ($configuredCategory !== '') {
            return $configuredCategory;
        }

        $category = $post->categories->first(fn ($category): bool => $category->parent_id !== null)
            ?? $post->categories->first();

        return $this->plainText($category?->name);
    }

    /** @param array<string, mixed> $fields */
    protected function recipeCuisine(Post $post, array $fields): string
    {
        $configuredCuisine = $this->plainText($fields['recipe_cuisine'] ?? $fields['cuisine'] ?? null);

        if ($configuredCuisine !== '') {
            return $configuredCuisine;
        }

        $cuisineTag = $post->tags->first(
            fn ($tag): bool => in_array(Str::lower($tag->slug), self::CUISINE_TAG_SLUGS, true),
        );

        return $this->plainText($cuisineTag?->name);
    }

    /** @param array<string, mixed> $fields */
    protected function keywords(
        Post $post,
        array $fields,
        string $recipeCategory,
        string $recipeCuisine,
    ): string {
        $excludedValues = array_filter([
            Str::lower($recipeCategory),
            Str::lower($recipeCuisine),
        ]);
        $keywords = $post->tags
            ->map(fn ($tag): string => $this->plainText($tag->name))
            ->filter(fn (string $tag): bool => $tag !== '' && ! in_array(Str::lower($tag), $excludedValues, true));
        $difficulty = $this->plainText($fields['difficulty'] ?? null);

        if ($difficulty !== '' && ! in_array(Str::lower($difficulty), $excludedValues, true)) {
            $keywords->push($difficulty);
        }

        return $keywords
            ->unique(fn (string $keyword): string => Str::lower($keyword))
            ->implode(', ');
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
