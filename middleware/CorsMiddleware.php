<?php

class CorsMiddleware
{
    private const DEFAULT_METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
    private const DEFAULT_HEADERS = ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept'];
    private const DEFAULT_DEV_ORIGINS = [
        'http://localhost:3000',
        'http://127.0.0.1:3000',
        'http://localhost:4200',
        'http://127.0.0.1:4200',
    ];

    private static ?array $configCache = null;

    public static function clearCachedConfig(): void
    {
        self::$configCache = null;
    }

    public static function handle(string $method, bool $isSensitive = false): bool
    {
        $config = self::config();
        $allowedOrigins = self::resolveAllowedOrigins($config, $isSensitive);
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? trim((string)$_SERVER['HTTP_ORIGIN']) : '';

        $originDecision = self::decideOrigin($origin, $allowedOrigins, $config['allow_credentials']);

        if (!$originDecision['allowed'] && $origin !== '') {
            self::respondForbiddenOrigin();
            return false;
        }

        self::emitHeaders($originDecision, $config);

        if (strtoupper($method) === 'OPTIONS') {
            http_response_code(204);
            return false;
        }

        return true;
    }

    private static function respondForbiddenOrigin(): void
    {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Credentials: false');
        echo json_encode([
            'success' => false,
            'error' => 'cors_forbidden',
            'message' => 'The requested origin is not allowed for this endpoint.',
        ], JSON_THROW_ON_ERROR);
    }

    private static function emitHeaders(array $originDecision, array $config): void
    {
        if ($originDecision['value'] !== null) {
            header('Access-Control-Allow-Origin: ' . $originDecision['value']);
            if ($originDecision['vary']) {
                header('Vary: Origin');
            }
        }

        header('Access-Control-Allow-Credentials: ' . ($config['allow_credentials'] ? 'true' : 'false'));
        header('Access-Control-Allow-Methods: ' . implode(', ', $config['allowed_methods']));
        header('Access-Control-Allow-Headers: ' . implode(', ', $config['allowed_headers']));

        if (!empty($config['expose_headers'])) {
            header('Access-Control-Expose-Headers: ' . implode(', ', $config['expose_headers']));
        }

        if ($config['max_age'] !== null) {
            header('Access-Control-Max-Age: ' . (int)$config['max_age']);
        }
    }

    private static function decideOrigin(string $origin, array $allowedOrigins, bool $allowCredentials): array
    {
        $origin = trim($origin);

        if ($origin === '') {
            if (in_array('*', $allowedOrigins, true)) {
                return ['allowed' => true, 'value' => '*', 'vary' => false];
            }

            return ['allowed' => true, 'value' => null, 'vary' => false];
        }

        if (in_array($origin, $allowedOrigins, true)) {
            return ['allowed' => true, 'value' => $origin, 'vary' => true];
        }

        if (in_array('*', $allowedOrigins, true)) {
            $value = $allowCredentials ? $origin : '*';
            return ['allowed' => true, 'value' => $value, 'vary' => $allowCredentials];
        }

        return ['allowed' => false, 'value' => null, 'vary' => false];
    }

    private static function resolveAllowedOrigins(array $config, bool $isSensitive): array
    {
        $environment = $config['environment'];
        $isProduction = strtolower($environment) === 'production';

        $baseOrigins = $config['allowed_origins'];
        $sensitiveOrigins = $config['sensitive_origins'];
        $devOrigins = $config['dev_origins'];

        $origins = $isSensitive ? ($sensitiveOrigins ?: $baseOrigins) : $baseOrigins;

        if (!$isProduction) {
            $origins = array_merge($origins, $devOrigins);
        }

        if ($isSensitive && $isProduction) {
            $filtered = array_filter($origins, static fn($origin) => $origin !== '*');
            $origins = array_values($filtered);
        }

        $origins = array_map(static fn($origin) => trim((string)$origin), $origins);
        $origins = array_filter($origins, static fn($origin) => $origin !== '');
        $origins = array_values(array_unique($origins));

        if (empty($origins) && !$isProduction) {
            $origins = self::DEFAULT_DEV_ORIGINS;
        }

        return $origins;
    }

    private static function config(): array
    {
        if (self::$configCache !== null) {
            return self::$configCache;
        }

        $defaults = [
            'environment' => 'production',
            'allow_credentials' => true,
            'allowed_methods' => self::DEFAULT_METHODS,
            'allowed_headers' => self::DEFAULT_HEADERS,
            'expose_headers' => [],
            'max_age' => 600,
            'allowed_origins' => [],
            'sensitive_origins' => [],
            'dev_origins' => self::DEFAULT_DEV_ORIGINS,
        ];

        $envData = self::loadEnv();

        $config = $defaults;
        $config['environment'] = self::stringValue($envData, 'APP_ENV', $defaults['environment']);
        $config['allow_credentials'] = self::boolValue($envData, 'API_CORS_ALLOW_CREDENTIALS', $defaults['allow_credentials']);
        $config['allowed_methods'] = self::arrayValue($envData, 'API_CORS_ALLOWED_METHODS', $defaults['allowed_methods'], true);
        $config['allowed_headers'] = self::arrayValue($envData, 'API_CORS_ALLOWED_HEADERS', $defaults['allowed_headers']);
        $config['expose_headers'] = self::arrayValue($envData, 'API_CORS_EXPOSE_HEADERS', $defaults['expose_headers']);
        $config['max_age'] = self::intValue($envData, 'API_CORS_MAX_AGE', $defaults['max_age']);
        $config['allowed_origins'] = self::arrayValue($envData, 'API_CORS_ALLOWED_ORIGINS', $defaults['allowed_origins']);
        $config['sensitive_origins'] = self::arrayValue($envData, 'API_CORS_SENSITIVE_ORIGINS', $defaults['sensitive_origins']);
        $config['dev_origins'] = self::arrayValue($envData, 'API_CORS_DEV_ORIGINS', $defaults['dev_origins']);

        if (!in_array('OPTIONS', $config['allowed_methods'], true)) {
            $config['allowed_methods'][] = 'OPTIONS';
        }

        $config['allowed_methods'] = array_values(array_unique(array_map('strtoupper', $config['allowed_methods'])));
        $config['allowed_headers'] = array_values(array_unique(array_map('trim', $config['allowed_headers'])));
        $config['expose_headers'] = array_values(array_unique(array_map('trim', $config['expose_headers'])));
        $config['dev_origins'] = array_values(array_unique(array_map('trim', $config['dev_origins'])));

        return self::$configCache = $config;
    }

    private static function loadEnv(): array
    {
        $envPath = defined('APP_PATH') ? APP_PATH . '/.env' : __DIR__ . '/../.env';
        $envData = [];

        if (is_readable($envPath)) {
            $parsed = @parse_ini_file($envPath, false, INI_SCANNER_RAW);
            if (is_array($parsed)) {
                foreach ($parsed as $key => $value) {
                    $envData[strtoupper($key)] = $value;
                }
            }
        }

        foreach (array_keys($envData) as $key) {
            $envValue = getenv($key);
            if ($envValue !== false && $envValue !== null) {
                $envData[$key] = $envValue;
            }
        }

        foreach ($_ENV as $key => $value) {
            $upper = strtoupper((string)$key);
            if (!array_key_exists($upper, $envData) || $value !== null) {
                $envData[$upper] = $value;
            }
        }

        $trackedKeys = [
            'APP_ENV',
            'API_CORS_ALLOWED_ORIGINS',
            'API_CORS_SENSITIVE_ORIGINS',
            'API_CORS_DEV_ORIGINS',
            'API_CORS_ALLOW_CREDENTIALS',
            'API_CORS_ALLOWED_METHODS',
            'API_CORS_ALLOWED_HEADERS',
            'API_CORS_EXPOSE_HEADERS',
            'API_CORS_MAX_AGE',
        ];

        foreach ($trackedKeys as $key) {
            $envValue = getenv($key);
            if ($envValue !== false) {
                $envData[$key] = $envValue;
                continue;
            }

            if (isset($_ENV[$key])) {
                $envData[$key] = $_ENV[$key];
            }
        }

        return $envData;
    }

    private static function stringValue(array $envData, string $key, string $default): string
    {
        if (!array_key_exists($key, $envData)) {
            return $default;
        }

        $value = $envData[$key];
        if ($value === null || $value === '') {
            return $default;
        }

        return (string)$value;
    }

    private static function boolValue(array $envData, string $key, bool $default): bool
    {
        if (!array_key_exists($key, $envData) || $envData[$key] === '' || $envData[$key] === null) {
            return $default;
        }

        $value = strtolower((string)$envData[$key]);
        if (in_array($value, ['1', 'true', 'yes', 'on'], true)) {
            return true;
        }

        if (in_array($value, ['0', 'false', 'no', 'off'], true)) {
            return false;
        }

        return $default;
    }

    private static function intValue(array $envData, string $key, ?int $default): ?int
    {
        if (!array_key_exists($key, $envData) || $envData[$key] === '' || $envData[$key] === null) {
            return $default;
        }

        $value = (int)$envData[$key];

        return $value >= 0 ? $value : $default;
    }

    private static function arrayValue(array $envData, string $key, array $default, bool $upper = false): array
    {
        if (!array_key_exists($key, $envData)) {
            return $default;
        }

        $raw = $envData[$key];
        if ($raw === null) {
            return $default;
        }

        if ($raw === '') {
            return [];
        }

        if (is_array($raw)) {
            return $raw;
        }

        $items = array_map('trim', explode(',', (string)$raw));
        $items = array_filter($items, static fn($value) => $value !== '');
        if ($upper) {
            $items = array_map('strtoupper', $items);
        }

        return array_values(array_unique($items));
    }
}
