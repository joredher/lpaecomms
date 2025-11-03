<?php

declare(strict_types=1);

final class ApiResponder
{
    public static function json(array $data, int $status = 200, array $headers = []): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        foreach ($headers as $name => $value) {
            header($name . ': ' . $value);
        }

        echo json_encode($data, JSON_THROW_ON_ERROR);
    }

    public static function success(array $data = [], int $status = 200, array $headers = []): void
    {
        self::json(['success' => true] + $data, $status, $headers);
    }

    public static function error(string $message, int $status = 400, string $code = 'error', array $extra = []): void
    {
        $payload = array_merge([
            'success' => false,
            'error' => $code,
            'message' => $message,
        ], $extra);

        self::json($payload, $status);
    }
}

function api_read_json_body(): array
{
    $raw = file_get_contents('php://input') ?: '';
    if ($raw === '') {
        return [];
    }

    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
        ApiResponder::error('Invalid JSON payload.', 400, 'invalid_json');
        exit;
    }

    return $data;
}

function api_current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function api_require_user(): array
{
    $user = api_current_user();
    if (!$user) {
        ApiResponder::error('Authentication required.', 401, 'unauthenticated');
        exit;
    }

    return $user;
}
