<?php

if (!function_exists('getCatalogCacheConfig')) {
    function getCatalogCacheConfig(): array
    {
        static $config;

        if ($config !== null) {
            return $config;
        }

        $envPath = defined('APP_PATH') ? APP_PATH . '/.env' : __DIR__ . '/../.env';
        $env = [];
        if (is_file($envPath) && is_readable($envPath)) {
            $env = parse_ini_file($envPath);
        }

        $config = [
            'host' => $env['REDIS_HOST'] ?? '127.0.0.1',
            'port' => isset($env['REDIS_PORT']) ? (int)$env['REDIS_PORT'] : 6379,
            'ttl'  => isset($env['CATALOG_CACHE_TTL']) ? max(0, (int)$env['CATALOG_CACHE_TTL']) : 300,
        ];

        return $config;
    }
}

if (!function_exists('getRedisClient')) {
    function getRedisClient(): ?\Redis
    {
        static $client;
        static $failed = false;

        if ($failed) {
            return null;
        }

        if ($client instanceof \Redis) {
            return $client;
        }

        if (!class_exists(\Redis::class)) {
            $failed = true;
            return null;
        }

        $config = getCatalogCacheConfig();
        $client = new \Redis();

        try {
            $client->connect($config['host'], $config['port']);
        } catch (\RedisException $e) {
            error_log('⚠️ Redis connection failed: ' . $e->getMessage());
            $failed = true;
            return null;
        }

        return $client;
    }
}

if (!function_exists('getCatalogListing')) {
    function getCatalogListing(string $cacheKey): ?string
    {
        $client = getRedisClient();
        if (!$client) {
            return null;
        }

        try {
            $value = $client->get($cacheKey);
            return $value === false ? null : $value;
        } catch (\RedisException $e) {
            error_log('⚠️ Redis read failed: ' . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('setCatalogListing')) {
    function setCatalogListing(string $cacheKey, $payload, ?int $ttlSeconds = null): bool
    {
        $client = getRedisClient();
        if (!$client) {
            return false;
        }

        if (is_array($payload) || is_object($payload)) {
            $payload = json_encode($payload);
            if ($payload === false) {
                error_log('⚠️ Redis payload encoding failed: ' . json_last_error_msg());
                return false;
            }
        }

        if (!is_string($payload)) {
            $payload = (string)$payload;
        }

        $config = getCatalogCacheConfig();
        $ttl = $ttlSeconds ?? $config['ttl'];

        try {
            if ($ttl > 0) {
                return (bool)$client->setex($cacheKey, $ttl, $payload);
            }

            return (bool)$client->set($cacheKey, $payload);
        } catch (\RedisException $e) {
            error_log('⚠️ Redis write failed: ' . $e->getMessage());
            return false;
        }
    }
}
