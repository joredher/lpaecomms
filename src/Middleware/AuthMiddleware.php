<?php

namespace Lpaecomms\Middleware;

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
}
