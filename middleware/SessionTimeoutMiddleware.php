<?php

class SessionTimeoutMiddleware
{
    /**
     * Handles session idle timeout. If a logged-in user has been idle longer
     * than the configured threshold, the session is terminated and a cookie
     * flag is set so the UI can show a blocking modal.
     */
    public static function handle(): void
    {
        // Never interfere with CORS preflight
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($method === 'OPTIONS') {
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Only applies to authenticated users
        if (!isset($_SESSION['user']['id'])) {
            return;
        }

        $timeout = self::resolveTimeoutSeconds();
        $now = time();
        $last = isset($_SESSION['last_activity_at']) ? (int)$_SESSION['last_activity_at'] : $now;

        // If expired, kill session and notify client via cookie/response
        if (($now - $last) >= $timeout) {
            self::expireSession();

            $path = trim((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
            $isApi = str_starts_with($path, 'api');

            if ($isApi) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                header('X-Session-Expired: 1');
                echo json_encode([
                    'success' => false,
                    'error' => 'session_expired',
                    'message' => 'Your session expired due to inactivity.',
                ], JSON_THROW_ON_ERROR);
                exit;
            }

            // For non-API requests let the page render; the cookie will trigger the modal.
            return;
        }

        // Update activity timestamp for active sessions
        $_SESSION['last_activity_at'] = $now;
    }

    private static function resolveTimeoutSeconds(): int
    {
        // Default to 20 minutes; use 5 minutes in development
        $defaults = [
            'prod' => 20 * 60,
            'dev'  => 5 * 60,
        ];

        $envPath = defined('APP_PATH') ? APP_PATH . '/.env' : __DIR__ . '/../.env';
        $env = [];
        if (is_readable($envPath)) {
            $parsed = @parse_ini_file($envPath, false, INI_SCANNER_RAW);
            if (is_array($parsed)) {
                $env = $parsed;
            }
        }

        $configured = (int)($env['SESSION_IDLE_TIMEOUT_SECONDS'] ?? 0);
        if ($configured > 0) {
            return $configured;
        }

        $appEnv = strtolower((string)($env['APP_ENV'] ?? ''));
        if ($appEnv === 'development' || $appEnv === 'local' || $appEnv === 'dev') {
            return $defaults['dev'];
        }

        $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
        if ($host === 'localhost' || str_starts_with($host, '127.0.0.1') || str_ends_with($host, '.test') || str_ends_with($host, '.local')) {
            return $defaults['dev'];
        }

        return $defaults['prod'];
    }

    private static function expireSession(): void
    {
        // Destroy current session
        session_unset();
        session_destroy();

        // Flag for the UI via cookie (works even without session)
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        setcookie(
            'lpa_idle_logout',
            '1',
            [
                'expires' => time() + 1800, // 30 minutes visibility
                'path' => '/',
                'secure' => $secure,
                'httponly' => false, // readable by JS to immediately show modal
                'samesite' => 'Lax',
            ]
        );

        // Start a fresh anonymous session to avoid warnings later in request lifecycle
        session_start();
        session_regenerate_id(true);
    }
}
