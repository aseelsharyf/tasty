<?php

namespace App\Support;

class BotDetector
{
    /** @var array<int, string> */
    protected static array $botPatterns = [
        'Googlebot',
        'Bingbot',
        'Slurp',
        'DuckDuckBot',
        'Baiduspider',
        'YandexBot',
        'Sogou',
        'facebookexternalhit',
        'Twitterbot',
        'LinkedInBot',
        'WhatsApp',
        'TelegramBot',
        'Applebot',
        'AhrefsBot',
        'SemrushBot',
        'MJ12bot',
        'DotBot',
        'PetalBot',
        'curl',
        'wget',
        'python-requests',
        'Scrapy',
        'Go-http-client',
        'Java/',
        'Apache-HttpClient',
        'bot',
        'crawler',
        'spider',
        'headless',
        'PhantomJS',
        'Lighthouse',
        'GTmetrix',
        'Pingdom',
        'UptimeRobot',
    ];

    public static function isBot(?string $userAgent): bool
    {
        if ($userAgent === null || $userAgent === '') {
            return true;
        }

        $lowerAgent = mb_strtolower($userAgent);

        foreach (static::$botPatterns as $pattern) {
            if (str_contains($lowerAgent, mb_strtolower($pattern))) {
                return true;
            }
        }

        return false;
    }
}
