<?php
require_once __DIR__ . '/../../bootstrap.php';

loadRepo('middleware/AuthMiddleware.php');

class AdminController
{
    public function __construct()
    {
        AuthMiddleware::adminOnly();
    }

    public function index(): void
    {
        $title = 'Admin Dashboard';
        $pageContent = 'pages/admin/dashboard.php';
        include 'includes/admin/layout.php';
    }
}
