<?php

namespace App\Support;

use Carbon\CarbonImmutable;

class CmsDateTime
{
    public static function toUtc(string $value): CarbonImmutable
    {
        $hasExplicitTimezone = preg_match('/(?:Z|[+-]\d{2}:?\d{2})$/i', trim($value)) === 1;
        $timezone = $hasExplicitTimezone ? null : config('cms.timezone');

        return CarbonImmutable::parse($value, $timezone)->utc();
    }

    public static function toUtcString(string $value): string
    {
        return self::toUtc($value)->toIso8601String();
    }
}
