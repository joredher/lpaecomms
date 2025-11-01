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

        $envData = [];

        $envPath = defined('APP_PATH') ? APP_PATH . '/.env' : __DIR__ . '/../.env';
        if (is_readable($envPath)) {
            $parsed = @parse_ini_file($envPath, false, INI_SCANNER_TYPED);
            if (is_array($parsed)) {
                $envData = array_change_key_case($parsed, CASE_UPPER);
            }
        }

        foreach (['REDIS_HOST', 'REDIS_PORT', 'CATALOG_CACHE_TTL'] as $key) {
            $envValue = getenv($key);
            if ($envValue === false && isset($_ENV[$key])) {
                $envValue = $_ENV[$key];
            }

            if ($envValue !== false && $envValue !== null && $envValue !== '') {
                $envData[$key] = $envValue;
            }
        }

        $config = array_merge($defaults, $envData);

        $config['REDIS_PORT'] = (int)$config['REDIS_PORT'];
        $config['CATALOG_CACHE_TTL'] = max(0, (int)$config['CATALOG_CACHE_TTL']);

        return $config;
    }
}

if (!function_exists('catalogCacheClient')) {
    function catalogCacheClient()
    {
        static $clientInitialized = false;
        static $client = null;

        if ($clientInitialized) {
            return $client;
        }

        $config = catalogCacheConfig();

        if (class_exists('Redis')) {
            $redis = new \Redis();

            try {
                $redis->connect($config['REDIS_HOST'], (int)$config['REDIS_PORT']);
                $client = $redis;
                $clientInitialized = true;

                return $client;
            } catch (\RedisException $exception) {
                $client = null;
            }
        }

        if (class_exists('Predis\\Client')) {
            try {
                $predis = new \Predis\Client([
                    'scheme' => 'tcp',
                    'host' => $config['REDIS_HOST'],
                    'port' => (int)$config['REDIS_PORT'],
                ]);

                // Force connection to fail fast if Redis is unreachable.
                $predis->connect();

                $client = $predis;
            } catch (\Exception $exception) {
                $client = null;
            }
        }

        $clientInitialized = true;

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
