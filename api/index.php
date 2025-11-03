<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/support/helpers.php';
require_once __DIR__ . '/support/cors.php';
require_once __DIR__ . '/../middleware/SessionTimeoutMiddleware.php';
require_once __DIR__ . '/controllers/AuthApiController.php';
require_once __DIR__ . '/controllers/CatalogueApiController.php';
require_once __DIR__ . '/controllers/CartApiController.php';
require_once __DIR__ . '/controllers/CheckoutApiController.php';
require_once __DIR__ . '/controllers/OrdersApiController.php';
require_once __DIR__ . '/controllers/AccountApiController.php';

api_handle_cors();

SessionTimeoutMiddleware::handle();

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$uriPath = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
if ($uriPath === 'api') {
    $path = '';
} elseif (str_starts_with($uriPath, 'api/')) {
    $path = substr($uriPath, 4);
} else {
    $path = $uriPath;
}

try {
    switch ($method) {
        case 'GET':
            $handler = match ($path) {
                'auth.me' => fn() => AuthApiController::me(),
                'catalogue/filters' => fn() => CatalogueApiController::filters(),
                'catalogue' => fn() => CatalogueApiController::index(),
                'cart' => fn() => CartApiController::show(),
                'checkout/start' => fn() => CheckoutApiController::start(),
                'orders' => fn() => OrdersApiController::index(),
                'account/profile' => fn() => AccountApiController::profile(),
                default => function () use ($path) {
                    ApiResponder::error('Route not found: ' . $path, 404, 'not_found');
                }
            };
            $handler();
            break;
        case 'POST':
            $handler = match ($path) {
                'auth.login' => fn() => AuthApiController::login(),
                'auth.validate' => fn() => AuthApiController::validate(),
                'auth.logout' => fn() => AuthApiController::logout(),
                'cart.add' => fn() => CartApiController::add(),
                'checkout/address' => fn() => CheckoutApiController::saveAddress(),
                'checkout/process' => fn() => CheckoutApiController::process(),
                'account/change-password' => fn() => AccountApiController::changePassword(),
                default => function () use ($path) {
                    ApiResponder::error('Route not found: ' . $path, 404, 'not_found');
                }
            };
            $handler();
            break;
        case 'PUT':
            $handler = match ($path) {
                'account/profile' => fn() => AccountApiController::updateProfile(),
                default => function () use ($path) {
                    ApiResponder::error('Route not found: ' . $path, 404, 'not_found');
                }
            };
            $handler();
            break;
        default:
            ApiResponder::error('Method not allowed.', 405, 'method_not_allowed');
    }
} catch (Throwable $exception) {
    ApiResponder::error('Unexpected server error.', 500, 'server_error');
}
