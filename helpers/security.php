<?php

if (!function_exists('e')) {
    function e(mixed $value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('ea')) {
    function ea(mixed $value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('eu')) {
    function eu(mixed $value): string {
        return rawurlencode((string)$value);
    }
}

if (!function_exists('internal_path')) {
    function internal_path(?string $path): string {
        $path = (string)$path;
        // Strip scheme/host to avoid open redirects
        $path = preg_replace('#^[a-zA-Z]+://[^/]+#', '', $path) ?? $path;
        // Force relative path; disallow control characters and backslashes
        $path = preg_replace('#[^\x20-\x7E]#', '', $path) ?? $path;
        if ($path === '' || $path[0] !== '/') {
            $path = '/' . ltrim($path, '/');
        }
        // Normalize path traversal
        $parts = [];
        foreach (explode('/', $path) as $seg) {
            if ($seg === '' || $seg === '.') continue;
            if ($seg === '..') { array_pop($parts); continue; }
            $parts[] = $seg;
        }
        return '/' . implode('/', $parts);
    }
}

final class Input
{
    public static function int(array $src, string $key, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX): ?int
    {
        if (!array_key_exists($key, $src)) return null;
        $val = filter_var($src[$key], FILTER_VALIDATE_INT);
        if ($val === false) return null;
        if ($val < $min || $val > $max) return null;
        return (int)$val;
    }

    public static function string(array $src, string $key, int $maxLen = 255, ?string $pattern = null): ?string
    {
        if (!array_key_exists($key, $src)) return null;
        $val = trim((string)$src[$key]);
        if ($val === '') return null;
        if (strlen($val) > $maxLen) $val = substr($val, 0, $maxLen);
        if ($pattern && !preg_match($pattern, $val)) return null;
        return $val;
    }

    /** @return int[] */
    public static function ints(array $src, string $key): array
    {
        $vals = $src[$key] ?? [];
        if (!is_array($vals)) $vals = [$vals];
        $out = [];
        foreach ($vals as $v) {
            $iv = filter_var($v, FILTER_VALIDATE_INT);
            if ($iv !== false) $out[] = (int)$iv;
        }
        return $out;
    }
}

// CSRF utilities (opt-in; not enforced globally yet)
if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string {
        return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('csrf_verify')) {
    function csrf_verify(): bool {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) return true;
        $token = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return is_string($token) && $token !== '' && hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }
}

