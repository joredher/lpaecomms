<?php

class SessionController
{
    public function keepAlive(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        header('Content-Type: application/json; charset=utf-8');

        // Only extend if user is authenticated
        if (empty($_SESSION['user']['id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'unauthenticated']);
            return;
        }

        // Touch session (SessionTimeoutMiddleware updates last_activity_at automatically)
        $_SESSION['last_activity_at'] = time();
        echo json_encode(['ok' => true, 'now' => $_SESSION['last_activity_at']]);
    }
}

