<?php

/**
 * @see https://github.com/artesaos/seotools
 */

return [
    'inertia' => env('SEO_TOOLS_INERTIA', false),
    'meta' => [
        /*
         * The default configurations to be used by the meta generator.
         */
        'defaults' => [
            'title' => env('APP_NAME', 'Tasty'),
            'titleBefore' => false,
            'description' => 'Your destination for food stories, recipes, and restaurant reviews.',
            'separator' => ' | ',
            'keywords' => ['food', 'recipes', 'restaurants', 'cooking', 'reviews'],
            'canonical' => 'full',
            'robots' => 'index,follow',
        ],
        /*
         * Webmaster tags are always added.
         */
        'webmaster_tags' => [
            'google' => env('SEO_GOOGLE_SITE_VERIFICATION'),
            'bing' => env('SEO_BING_SITE_VERIFICATION'),
            'alexa' => null,
            'pinterest' => env('SEO_PINTEREST_SITE_VERIFICATION'),
            'yandex' => null,
            'norton' => null,
        ],

        'add_notranslate_class' => false,
    ],
    'opengraph' => [
        /*
         * The default configurations to be used by the opengraph generator.
         */
        'defaults' => [
            'title' => env('APP_NAME', 'Tasty'),
            'description' => 'Your destination for food stories, recipes, and restaurant reviews.',
            'url' => null,
            'type' => 'website',
            'site_name' => env('APP_NAME', 'Tasty'),
            'images' => [],
        ],
    ],
    'twitter' => [
        /*
         * The default values to be used by the twitter cards generator.
         */
        'defaults' => [
            'card' => 'summary_large_image',
            'site' => env('TWITTER_SITE_HANDLE'),
        ],
    ],
    'json-ld' => [
        /*
         * The default configurations to be used by the json-ld generator.
         */
        'defaults' => [
            'title' => env('APP_NAME', 'Tasty'),
            'description' => 'Your destination for food stories, recipes, and restaurant reviews.',
            'url' => 'full',
            'type' => 'WebPage',
            'images' => [],
        ],
    ],
];
