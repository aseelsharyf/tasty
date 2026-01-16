<?php

namespace App\Services\Layouts;

use App\Models\Post;

class SectionDataResolver
{
    /**
     * Resolve data for a section based on its configuration.
     *
     * @param  array<string, mixed>  $section
     * @return array<string, mixed>
     */
    public function resolve(array $section): array
    {
        $type = $section['type'] ?? '';
        $config = $section['config'] ?? [];
        $dataSource = $section['dataSource'] ?? ['action' => 'recent', 'params' => []];
        $slots = $section['slots'] ?? [];

        // Parse slots into categorized arrays
        $slotData = $this->parseSlots($slots);

        // Build component parameters based on section type
        return match ($type) {
            'hero' => $this->resolveHero($config, $dataSource, $slotData),
            'latest-updates' => $this->resolveLatestUpdates($config, $dataSource, $slotData),
            'spread' => $this->resolveSpread($config, $dataSource, $slotData),
            'review' => $this->resolveReview($config, $dataSource, $slotData),
            'recipe' => $this->resolveRecipe($config, $dataSource, $slotData),
            'featured-person' => $this->resolveFeaturedPerson($config, $dataSource, $slotData),
            'featured-video' => $this->resolveFeaturedVideo($config, $dataSource, $slotData),
            'featured-location' => $this->resolveFeaturedLocation($config, $dataSource, $slotData),
            'feature-1' => $this->resolveFeature1($config, $dataSource, $slotData),
            'feature-2' => $this->resolveFeature2($config, $dataSource, $slotData),
            'newsletter' => $this->resolveNewsletter($config),
            'add-to-cart' => $this->resolveAddToCart($config, $slotData),
            default => [],
        };
    }

    /**
     * Parse slots into categorized data.
     *
     * @param  array<int, array<string, mixed>>  $slots
     * @return array{manual: array<int, int>, static: array<int, array<string, mixed>>, dynamicCount: int, totalSlots: int}
     */
    protected function parseSlots(array $slots): array
    {
        $manual = [];      // index => postId or productId
        $static = [];      // index => content array
        $dynamicCount = 0;

        foreach ($slots as $slot) {
            $index = $slot['index'] ?? 0;
            $mode = $slot['mode'] ?? 'dynamic';

            if ($mode === 'manual' && ! empty($slot['postId'])) {
                $manual[$index] = $slot['postId'];
            } elseif ($mode === 'manual' && ! empty($slot['productId'])) {
                // Support productId for add-to-cart section
                $manual[$index] = $slot['productId'];
            } elseif ($mode === 'static' && ! empty($slot['content'])) {
                $static[$index] = $slot['content'];
            } else {
                $dynamicCount++;
            }
        }

        return [
            'manual' => $manual,
            'static' => $static,
            'dynamicCount' => $dynamicCount,
            'totalSlots' => count($slots),
        ];
    }

    /**
     * Build data source parameters for querying posts.
     *
     * @param  array<string, mixed>  $dataSource
     * @return array<string, mixed>
     */
    protected function buildParams(array $dataSource): array
    {
        $action = $dataSource['action'] ?? 'recent';
        $params = $dataSource['params'] ?? [];

        // Handle multiple slugs (new format)
        if ($action === 'byTag') {
            if (isset($params['slugs']) && is_array($params['slugs'])) {
                return ['tags' => $params['slugs']];
            }
            // Legacy single slug support
            if (isset($params['slug'])) {
                return ['tags' => [$params['slug']]];
            }
        }

        if ($action === 'byCategory') {
            if (isset($params['slugs']) && is_array($params['slugs'])) {
                return ['categories' => $params['slugs']];
            }
            // Legacy single slug support
            if (isset($params['slug'])) {
                return ['categories' => [$params['slug']]];
            }
        }

        return $params;
    }

    /**
     * Resolve Hero section data.
     *
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $dataSource
     * @param  array<string, mixed>  $slotData
     * @return array<string, mixed>
     */
    protected function resolveHero(array $config, array $dataSource, array $slotData): array
    {
        $data = [
            'alignment' => $config['alignment'] ?? 'center',
            'bgColor' => $config['bgColor'] ?? 'yellow',
            'buttonText' => $config['buttonText'] ?? 'Read More',
            'buttonColor' => $config['buttonColor'] ?? 'white',
        ];

        // Check for manual post assignment
        if (isset($slotData['manual'][0])) {
            $data['postId'] = $slotData['manual'][0];
        } elseif (isset($slotData['static'][0])) {
            // Static content
            $data['staticContent'] = $slotData['static'][0];
        } else {
            // Use dynamic action
            $data['action'] = $dataSource['action'] ?? 'recent';
            $data['params'] = $this->buildParams($dataSource);
        }

        return $data;
    }

    /**
     * Resolve Latest Updates section data.
     *
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $dataSource
     * @param  array<string, mixed>  $slotData
     * @return array<string, mixed>
     */
    protected function resolveLatestUpdates(array $config, array $dataSource, array $slotData): array
    {
        $data = [
            'introImage' => $config['introImage'] ?? '',
            'introImageAlt' => $config['introImageAlt'] ?? 'Latest Updates',
            'titleSmall' => $config['titleSmall'] ?? 'Latest',
            'titleLarge' => $config['titleLarge'] ?? 'Updates',
            'description' => $config['description'] ?? '',
            'buttonText' => $config['buttonText'] ?? 'More Updates',
            'showLoadMore' => $config['showLoadMore'] ?? true,
            // Data source for dynamic slots
            'action' => $dataSource['action'] ?? 'recent',
            'params' => $this->buildParams($dataSource),
            // Slot configuration
            'totalSlots' => $slotData['totalSlots'],
            'manualPostIds' => $slotData['manual'],
            'staticContent' => $slotData['static'],
            'dynamicCount' => $slotData['dynamicCount'],
        ];

        return $data;
    }

    /**
     * Resolve Spread section data.
     *
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $dataSource
     * @param  array<string, mixed>  $slotData
     * @return array<string, mixed>
     */
    protected function resolveSpread(array $config, array $dataSource, array $slotData): array
    {
        $data = [
            'showIntro' => $config['showIntro'] ?? true,
            'introImage' => $config['introImage'] ?? '',
            'introImageAlt' => $config['introImageAlt'] ?? 'The Spread',
            'titleSmall' => $config['titleSmall'] ?? 'The',
            'titleLarge' => $config['titleLarge'] ?? 'SPREAD',
            'description' => $config['description'] ?? '',
            'bgColor' => $config['bgColor'] ?? 'yellow',
            'showDividers' => $config['showDividers'] ?? true,
            'dividerColor' => $config['dividerColor'] ?? 'white',
            'mobileLayout' => $config['mobileLayout'] ?? 'scroll',
            // Data source for dynamic slots
            'action' => $dataSource['action'] ?? 'recent',
            'params' => $this->buildParams($dataSource),
            // Slot configuration
            'totalSlots' => $slotData['totalSlots'],
            'manualPostIds' => $slotData['manual'],
            'staticContent' => $slotData['static'],
            'dynamicCount' => $slotData['dynamicCount'],
        ];

        return $data;
    }

    /**
     * Resolve Review section data.
     *
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $dataSource
     * @param  array<string, mixed>  $slotData
     * @return array<string, mixed>
     */
    protected function resolveReview(array $config, array $dataSource, array $slotData): array
    {
        $data = [
            'showIntro' => $config['showIntro'] ?? true,
            'introImage' => $config['introImage'] ?? '',
            'introImageAlt' => $config['introImageAlt'] ?? 'On the Menu',
            'titleSmall' => $config['titleSmall'] ?? 'On the',
            'titleLarge' => $config['titleLarge'] ?? 'Menu',
            'description' => $config['description'] ?? '',
            'showDividers' => $config['showDividers'] ?? true,
            'dividerColor' => $config['dividerColor'] ?? 'white',
            'mobileLayout' => $config['mobileLayout'] ?? 'scroll',
            'buttonText' => $config['buttonText'] ?? 'More Reviews',
            'showLoadMore' => $config['showLoadMore'] ?? true,
            // Data source for dynamic slots
            'action' => $dataSource['action'] ?? 'recent',
            'params' => $this->buildParams($dataSource),
            // Slot configuration
            'totalSlots' => $slotData['totalSlots'],
            'manualPostIds' => $slotData['manual'],
            'staticContent' => $slotData['static'],
            'dynamicCount' => $slotData['dynamicCount'],
        ];

        return $data;
    }

    /**
     * Resolve Recipe section data.
     *
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $dataSource
     * @param  array<string, mixed>  $slotData
     * @return array<string, mixed>
     */
    protected function resolveRecipe(array $config, array $dataSource, array $slotData): array
    {
        $data = [
            'showIntro' => $config['showIntro'] ?? true,
            'introImage' => $config['introImage'] ?? '',
            'introImageAlt' => $config['introImageAlt'] ?? 'Everyday Cooking',
            'titleSmall' => $config['titleSmall'] ?? 'Everyday',
            'titleLarge' => $config['titleLarge'] ?? 'COOKING',
            'description' => $config['description'] ?? '',
            'bgColor' => $config['bgColor'] ?? 'yellow',
            'gradient' => $config['gradient'] ?? 'top',
            'mobileLayout' => $config['mobileLayout'] ?? 'grid',
            'showDividers' => $config['showDividers'] ?? false,
            'dividerColor' => $config['dividerColor'] ?? 'white',
            // Data source for dynamic slots
            'action' => $dataSource['action'] ?? 'recent',
            'params' => $this->buildParams($dataSource),
            // Slot configuration
            'totalSlots' => $slotData['totalSlots'],
            'manualPostIds' => $slotData['manual'],
            'staticContent' => $slotData['static'],
            'dynamicCount' => $slotData['dynamicCount'],
        ];

        return $data;
    }

    /**
     * Resolve Featured Person section data.
     *
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $dataSource
     * @param  array<string, mixed>  $slotData
     * @return array<string, mixed>
     */
    protected function resolveFeaturedPerson(array $config, array $dataSource, array $slotData): array
    {
        $data = [
            'tag1' => $config['tag1'] ?? 'TASTY FEATURE',
            'tag2' => $config['tag2'] ?? '',
            'buttonText' => $config['buttonText'] ?? 'Read More',
            'bgColor' => $config['bgColor'] ?? 'yellow',
        ];

        // Featured person - single slot
        if (isset($slotData['manual'][0])) {
            $data['postId'] = $slotData['manual'][0];
        } elseif (isset($slotData['static'][0])) {
            $data['staticContent'] = $slotData['static'][0];
        } else {
            $data['action'] = $dataSource['action'] ?? 'recent';
            $data['params'] = $this->buildParams($dataSource);
        }

        return $data;
    }

    /**
     * Resolve Featured Video section data.
     *
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $dataSource
     * @param  array<string, mixed>  $slotData
     * @return array<string, mixed>
     */
    protected function resolveFeaturedVideo(array $config, array $dataSource, array $slotData): array
    {
        $data = [
            'buttonText' => $config['buttonText'] ?? 'Watch',
            'overlayColor' => $config['overlayColor'] ?? '#FFE762',
            'showSectionGradient' => $config['showSectionGradient'] ?? true,
            'sectionGradientDirection' => $config['sectionGradientDirection'] ?? 'top',
            'sectionBgColor' => $config['sectionBgColor'] ?? '',
        ];

        // Featured video - single slot
        if (isset($slotData['manual'][0])) {
            $data['postId'] = $slotData['manual'][0];
        } elseif (isset($slotData['static'][0])) {
            $data['staticContent'] = $slotData['static'][0];
        } else {
            $data['action'] = $dataSource['action'] ?? 'recent';
            $data['params'] = $this->buildParams($dataSource);
        }

        return $data;
    }

    /**
     * Resolve Featured Location section data.
     *
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $dataSource
     * @param  array<string, mixed>  $slotData
     * @return array<string, mixed>
     */
    protected function resolveFeaturedLocation(array $config, array $dataSource, array $slotData): array
    {
        $data = [
            'tag1' => $config['tag1'] ?? 'TASTY FEATURE',
            'tag2' => $config['tag2'] ?? '',
            'bgColor' => $config['bgColor'] ?? 'yellow',
            'textColor' => $config['textColor'] ?? 'blue-black',
            'buttonVariant' => $config['buttonVariant'] ?? 'white',
            'buttonText' => $config['buttonText'] ?? 'Read More',
        ];

        // Featured location - single slot
        if (isset($slotData['manual'][0])) {
            $data['postId'] = $slotData['manual'][0];
        } elseif (isset($slotData['static'][0])) {
            $data['staticContent'] = $slotData['static'][0];
        } else {
            $data['action'] = $dataSource['action'] ?? 'recent';
            $data['params'] = $this->buildParams($dataSource);
        }

        return $data;
    }

    /**
     * Resolve Feature 1 section data.
     *
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $dataSource
     * @param  array<string, mixed>  $slotData
     * @return array<string, mixed>
     */
    protected function resolveFeature1(array $config, array $dataSource, array $slotData): array
    {
        $data = [
            'tag1' => $config['tag1'] ?? 'TASTY FEATURE',
            'tag2' => $config['tag2'] ?? '',
            'bgColor' => $config['bgColor'] ?? 'yellow',
            'textColor' => $config['textColor'] ?? 'blue-black',
            'buttonVariant' => $config['buttonVariant'] ?? 'white',
            'buttonText' => $config['buttonText'] ?? 'Read More',
        ];

        // Feature 1 - single slot
        if (isset($slotData['manual'][0])) {
            $data['postId'] = $slotData['manual'][0];
        } elseif (isset($slotData['static'][0])) {
            $data['staticContent'] = $slotData['static'][0];
        } else {
            $data['action'] = $dataSource['action'] ?? 'recent';
            $data['params'] = $this->buildParams($dataSource);
        }

        return $data;
    }

    /**
     * Resolve Feature 2 section data.
     *
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $dataSource
     * @param  array<string, mixed>  $slotData
     * @return array<string, mixed>
     */
    protected function resolveFeature2(array $config, array $dataSource, array $slotData): array
    {
        $data = [
            'tag1' => $config['tag1'] ?? 'TASTY FEATURE',
            'tag2' => $config['tag2'] ?? '',
            'bgColor' => $config['bgColor'] ?? 'blue-black',
            'textColor' => $config['textColor'] ?? 'white',
            'buttonVariant' => $config['buttonVariant'] ?? 'yellow',
            'buttonText' => $config['buttonText'] ?? 'Read More',
        ];

        // Feature 2 - single slot
        if (isset($slotData['manual'][0])) {
            $data['postId'] = $slotData['manual'][0];
        } elseif (isset($slotData['static'][0])) {
            $data['staticContent'] = $slotData['static'][0];
        } else {
            $data['action'] = $dataSource['action'] ?? 'recent';
            $data['params'] = $this->buildParams($dataSource);
        }

        return $data;
    }

    /**
     * Resolve Newsletter section data.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    protected function resolveNewsletter(array $config): array
    {
        return [
            'title' => $config['title'] ?? 'COME HUNGRY, LEAVE INSPIRED. SIGN UP FOR TASTY UPDATES.',
            'placeholder' => $config['placeholder'] ?? 'Enter your Email',
            'buttonText' => $config['buttonText'] ?? 'SUBSCRIBE',
            'bgColor' => $config['bgColor'] ?? '#F3F4F6',
        ];
    }

    /**
     * Resolve Add to Cart section data.
     *
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $slotData
     * @return array<string, mixed>
     */
    protected function resolveAddToCart(array $config, array $slotData): array
    {
        // Extract product IDs from manual slots
        $productIds = [];
        foreach ($slotData['manual'] as $index => $productId) {
            if (! empty($productId)) {
                $productIds[] = $productId;
            }
        }

        return [
            'title' => $config['title'] ?? 'ADD TO CART',
            'description' => $config['description'] ?? 'Ingredients, tools, and staples we actually use.',
            'bgColor' => $config['bgColor'] ?? 'white',
            'productIds' => $productIds,
            'count' => $slotData['totalSlots'] ?: 6,
        ];
    }
}
