<?php

if (!function_exists('catalogCacheConfig')) {
    function catalogCacheConfig(): array
    {
        static $config = null;

        if ($config !== null) {
            return $config;
        }

        $defaults = [
            'REDIS_HOST' => '127.0.0.1',
            'REDIS_PORT' => 6379,
            'CATALOG_CACHE_TTL' => 300,
        ];

        $envPath = defined('APP_PATH') ? APP_PATH . '/.env' : __DIR__ . '/../.env';

        if (is_readable($envPath)) {
            $env = @parse_ini_file($envPath, false, INI_SCANNER_TYPED);
            if (is_array($env)) {
                $config = array_merge($defaults, $env);
            }
        }

        if ($config === null) {
            $config = $defaults;
        }

        $config['REDIS_PORT'] = (int)($config['REDIS_PORT'] ?? 6379);
        $config['CATALOG_CACHE_TTL'] = max(0, (int)($config['CATALOG_CACHE_TTL'] ?? 300));

        return $config;
    }
}

if (!function_exists('catalogCacheClient')) {
    function catalogCacheClient(): ?\Redis
    {
        static $clientInitialized = false;
        static $client = null;

        if ($clientInitialized) {
            return $client;
        }

        $clientInitialized = true;

        if (!class_exists('Redis')) {
            return null;
        }

        $config = catalogCacheConfig();

        $redis = new \Redis();

        try {
            $redis->connect($config['REDIS_HOST'], (int)$config['REDIS_PORT']);
            $client = $redis;
        } catch (\RedisException $exception) {
            $client = null;
        }

        return $client;
    }
}

if (!function_exists('getCatalogListing')) {
    function getCatalogListing(string $cacheKey): ?string
    {
        $client = catalogCacheClient();
        if (!$client) {
            return null;
        }

        $value = $client->get($cacheKey);

        return $value === false ? null : $value;
    }
}

if (!function_exists('setCatalogListing')) {
    function setCatalogListing(string $cacheKey, string $payload, ?int $ttlSeconds = null): bool
    {
        $client = catalogCacheClient();
        if (!$client) {
            return false;
        }

        $config = catalogCacheConfig();
        $ttl = $ttlSeconds ?? $config['CATALOG_CACHE_TTL'];

        if ($ttl > 0) {
            return (bool)$client->setex($cacheKey, (int)$ttl, $payload);
        }

        return (bool)$client->set($cacheKey, $payload);
    }
}
