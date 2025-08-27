<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$uri = $_SERVER['REQUEST_URI'] ?? '/admin';

$links = [
    '/admin' => 'Dashboard',
    '/admin.users' => 'Users',
    '/admin.orders' => 'Orders',
    '/admin.products' => 'Products',
];

$style = 'text-decoration-none';
?>
<header class="header">
    <div class="logo-nav">
        <a href="/admin" class="text-decoration-none">
            <img class="logo" src="../assets/images/Logo.svg" alt="Logo">
        </a>
        <nav class="navigation">
            <?php foreach ($links as $route => $label): ?>
                <a href="<?= $route ?>" class="<?= strtolower($uri) === strtolower($route) ? 'text-color-selection text-decoration-underline' : $style ?>">
                    <?= htmlspecialchars($label) ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>
    <div class="actions">
        <div class="d-flex align-items-center gap-3">
            <a href="/home" class="text-decoration-none">View Site</a>
            <a href="/auth.logout" class="text-decoration-none login-link">Logout</a>
        </div>
    </div>
</header>
