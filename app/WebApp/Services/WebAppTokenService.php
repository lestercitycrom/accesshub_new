<?php

declare(strict_types=1);

namespace App\WebApp\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class WebAppTokenService
{
    private const TTL_MINUTES = 15;
    private const CACHE_PREFIX = 'webapp_token:';

    public function generate(int $telegramId): string
    {
        $token = Str::random(48);

        Cache::put(self::CACHE_PREFIX . $token, $telegramId, now()->addMinutes(self::TTL_MINUTES));

        return $token;
    }

    public function consume(string $token): ?int
    {
        $key = self::CACHE_PREFIX . $token;
        $telegramId = Cache::get($key);

        if ($telegramId === null) {
            return null;
        }

        Cache::forget($key);

        return (int) $telegramId;
    }
}
