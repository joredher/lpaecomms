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
        $isAdmin = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';
        if (!$isAdmin) {
            header('Location: /');
            exit;
        }
    }
}
