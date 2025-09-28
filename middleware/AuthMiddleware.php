<?php

class AuthMiddleware
{

    public static function guestOnly(): void
    {
        if (isset($_SESSION['user']['id'])) {
            header('Location: /');
            exit;
        }
    }

    public static function authOnly(): void
    {
        if (!isset($_SESSION['user']['id'])) {
            header('Location: /login');
            exit;
        }
    }

    public static function adminOnly(): void
    {
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
        $isFromGroupTwo = isset($_SESSION['user']) && $_SESSION['user']['group'] === 2;

        if ($user !== null):
            $isFromGroupTwo = $user['lpa_fk_user_group_ID'] === 2;
        endif;

        return $isFromGroupTwo;
    }

}
