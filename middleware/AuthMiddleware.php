<?php

class AuthMiddleware
{

    public static function guestOnly(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['user']['id'])) {
            header('Location: /');
            exit;
        }
    }

    public static function authOnly(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user']['id'])) {
            header('Location: /login');
            exit;
        }
    }

    public static function adminOnly(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $isAdmin = isset($_SESSION['user']['group']) && (int)$_SESSION['user']['group'] === 1;
        if (!$isAdmin) {
            $_SESSION['notFoundReason'] = 'Page not found';
            http_response_code(404);
            $pageContent = __DIR__ . '/../pages/error/404.php';
            include __DIR__ . '/../includes/layout.php';
            exit;
        }
    }

    public static function userOnly($user = null): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $isFromGroupTwo = isset($_SESSION['user']) && $_SESSION['user']['group'] === 2;

        if ($user !== null):
            $isFromGroupTwo = $user['lpa_fk_user_group_ID'] === 2;
        endif;

        return $isFromGroupTwo;
    }

    public static function abort403(string $reason = 'You do not have permission to access this resource.'): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        http_response_code(403);
        $_SESSION['forbiddenReason'] = $reason;
        $title = 'Forbidden';
        $pageContent = __DIR__ . '/../pages/error/403.php';
        include __DIR__ . '/../includes/layout.php';
        exit;
    }

}
