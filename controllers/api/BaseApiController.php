<?php

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

abstract class BaseApiController
{
    protected function respond(array $result): void
    {
        $status = $result['status'] ?? 200;
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        $payload = $result;
        unset($payload['status']);

        echo json_encode($payload, JSON_THROW_ON_ERROR);
    }

    protected function requireAuth(): ?array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user']['id'])) {
            return [
                'success' => false,
                'status' => 401,
                'message' => 'Authentication required.',
            ];
        }
        return null;
    }

    protected function requireCustomer(): ?array
    {
        if ($auth = $this->requireAuth()) {
            return $auth;
        }

        if (!AuthMiddleware::userOnly()) {
            return [
                'success' => false,
                'status' => 403,
                'message' => 'Only customer accounts may access this resource.',
                'error' => 'forbidden',
            ];
        }

        return null;
    }
}
