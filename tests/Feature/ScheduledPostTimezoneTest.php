<?php

use App\Support\CmsDateTime;

it('interprets timezone-less editorial dates as Maldives Time', function () {
    $scheduledAt = CmsDateTime::toUtc('2026-10-14T08:30');

    expect($scheduledAt->toIso8601String())->toBe('2026-10-14T03:30:00+00:00');
});

it('preserves an explicit timezone when converting to UTC', function () {
    $scheduledAt = CmsDateTime::toUtc('2026-10-14T08:30:00+00:00');

    expect($scheduledAt->toIso8601String())->toBe('2026-10-14T08:30:00+00:00');
});
