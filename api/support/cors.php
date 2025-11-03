<?php

declare(strict_types=1);

require_once __DIR__ . '/ApiConfig.php';

function api_handle_cors(): void
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $allowedOrigins = ApiConfig::allowedOrigins();
    $allowAll = in_array('*', $allowedOrigins, true);
    $isAllowed = $allowAll || ($origin !== '' && in_array($origin, $allowedOrigins, true));

    if ($allowAll) {
        header('Access-Control-Allow-Origin: *');
    } elseif ($isAllowed) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
    } elseif ($origin !== '') {
        ApiResponder::error('Origin not allowed.', 403, 'cors_not_allowed');
        exit;
    }

    if (ApiConfig::allowCredentials() && ($allowAll || $isAllowed)) {
        header('Access-Control-Allow-Credentials: true');
    }

    $allowedMethods = ApiConfig::allowedMethods();
    if ($allowedMethods !== '') {
        header('Access-Control-Allow-Methods: ' . $allowedMethods);
    }

    $allowedHeaders = ApiConfig::allowedHeaders();
    if ($allowedHeaders !== '') {
        header('Access-Control-Allow-Headers: ' . $allowedHeaders);
    }

    $exposeHeaders = ApiConfig::exposeHeaders();
    if ($exposeHeaders !== '') {
        header('Access-Control-Expose-Headers: ' . $exposeHeaders);
    }

    $maxAge = ApiConfig::maxAge();
    if ($maxAge > 0) {
        header('Access-Control-Max-Age: ' . $maxAge);
    }

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}
