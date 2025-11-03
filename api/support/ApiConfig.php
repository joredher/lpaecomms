<?php

declare(strict_types=1);

final class ApiConfig
{
    private static ?array $config = null;

    private static function bootstrap(): void
    {
        if (self::$config !== null) {
            return;
        }

        $defaults = [
            'API_BASE_URL'                => 'https://api.lpaecomms.local',
            'API_CORS_ALLOWED_ORIGINS'    => '',
            'API_CORS_SENSITIVE_ORIGINS'  => '',
            'API_CORS_DEV_ORIGINS'        => 'http://localhost:3000,http://127.0.0.1:3000',
            'API_CORS_ALLOW_CREDENTIALS'  => 'true',
            'API_CORS_ALLOWED_METHODS'    => 'GET,POST,PATCH,PUT,DELETE,OPTIONS',
            'API_CORS_ALLOWED_HEADERS'    => 'Content-Type,Authorization,X-Requested-With,Accept',
            'API_CORS_EXPOSE_HEADERS'     => '',
            'API_CORS_MAX_AGE'            => '600',
            'APP_ENV'                     => 'production',
        ];

        $envPath = defined('APP_PATH') ? APP_PATH . '/.env' : __DIR__ . '/../../.env';
        $envData = [];
        if (is_readable($envPath)) {
            $parsed = @parse_ini_file($envPath, false, INI_SCANNER_RAW);
            if (is_array($parsed)) {
                foreach ($parsed as $key => $value) {
                    $envData[strtoupper((string)$key)] = $value;
                }
            }
        }

        foreach (array_keys($defaults) as $key) {
            $envValue = getenv($key);
            if ($envValue === false && isset($_ENV[$key])) {
                $envValue = $_ENV[$key];
            }
            if ($envValue !== false && $envValue !== null && $envValue !== '') {
                $envData[$key] = $envValue;
            }
        }

        self::$config = array_merge($defaults, $envData);
    }

    public static function get(string $key, $default = null)
    {
        self::bootstrap();
        return self::$config[$key] ?? $default;
    }

    public static function allowedOrigins(): array
    {
        $allowed = self::csvToArray((string)self::get('API_CORS_ALLOWED_ORIGINS'));
        $sensitive = self::csvToArray((string)self::get('API_CORS_SENSITIVE_ORIGINS'));
        $dev = self::csvToArray((string)self::get('API_CORS_DEV_ORIGINS'));

        $useDev = self::shouldIncludeDevOrigins();
        $origins = array_merge($allowed, $sensitive, $useDev ? $dev : []);

        $unique = [];
        foreach ($origins as $origin) {
            if ($origin === '') {
                continue;
            }
            $unique[$origin] = true;
        }

        return array_keys($unique);
    }

    public static function allowCredentials(): bool
    {
        return self::toBool(self::get('API_CORS_ALLOW_CREDENTIALS', 'true'));
    }

    public static function allowedMethods(): string
    {
        return (string)self::get('API_CORS_ALLOWED_METHODS', 'GET,POST,OPTIONS');
    }

    public static function allowedHeaders(): string
    {
        return (string)self::get('API_CORS_ALLOWED_HEADERS', 'Content-Type');
    }

    public static function exposeHeaders(): string
    {
        return (string)self::get('API_CORS_EXPOSE_HEADERS', '');
    }

    public static function maxAge(): int
    {
        $value = (string)self::get('API_CORS_MAX_AGE', '600');
        return max(0, (int)$value);
    }

    public static function baseUrl(): string
    {
        return (string)self::get('API_BASE_URL', 'https://api.lpaecomms.local');
    }

    private static function shouldIncludeDevOrigins(): bool
    {
        $env = strtolower((string)self::get('APP_ENV', 'production'));
        if (in_array($env, ['dev', 'development', 'local'], true)) {
            return true;
        }

        $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
        if ($host === 'localhost' || str_starts_with($host, '127.0.0.1') || str_ends_with($host, '.local')) {
            return true;
        }

        return false;
    }

    private static function csvToArray(string $value): array
    {
        if ($value === '') {
            return [];
        }

        $parts = array_map('trim', explode(',', $value));
        return array_values(array_filter($parts, static fn($item) => $item !== ''));
    }

    private static function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $str = strtolower((string)$value);
        return in_array($str, ['1', 'true', 'on', 'yes'], true);
    }
}
