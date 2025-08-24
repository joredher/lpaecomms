<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = isset($_SESSION['user']);

$user = $_SESSION['user'] ?? null;

$uri = $_SERVER['REQUEST_URI'] ?? '/home';

if (!str_contains(strtolower($uri), '/cart')) {
    $_SESSION['previous_page'] = $_SESSION['current_page'] ?? '/home';
}

$_SESSION['current_page'] = $uri;



$style = 'text-decoration-none';
if (in_array(strtolower($uri), ['/home', '/products', '/about', '/contact'])) {
    $style = 'text-color-selection text-decoration-underline';
}



?>
<header class="header">
    <div class="logo-nav">
        <a href="/home" class="text-decoration-none">
            <img class="logo" src="../assets/images/Logo.svg" alt="Logo">
        </a>
        <nav class="navigation">
            <a href="/home" class="<?= strtolower($uri) === '/home' ? $style : '' ?>">Home</a>
            <a href="/products" class="<?= strtolower($uri) === '/products' ? $style : '' ?>">Products</a>
            <a href="/about" class="<?= strtolower($uri) === '/about' ? $style : '' ?>">About</a>
            <a href="/contact" class="<?= strtolower($uri) === '/contact' ? $style : '' ?>">Contact</a>
        </nav>
    </div>
    <div class="actions">
        <form action="/search.products" method="get">
            <div class="search-box">
                <button type="submit">
                    <img src="../assets/images/header/u_search.svg" alt="Search icon">
                </button>
                <label>
                    <input type="text" name="query" placeholder="Search something here!">
                </label>
            </div>
        </form>
        <div class="cart-icon">
            <a href="/cart" class="position-relative ms-3 text-decoration-none">
                <img src="../assets/images/header/u_cart.svg" alt="Cart icon">
<!--                --><?php //if (!empty($_SESSION['cart'])): ?>
                    <span id="cart-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">
                        <?= array_sum(array_column(@$_SESSION['cart'] ?? [], 'quantity')); ?>
                    </span>
<!--                --><?php //endif; ?>
            </a>
        </div>
        <div class="currency">
            <img src="../assets/images/header/Australia.svg" alt="Australia flag">
            <span>AUD</span>
            <img src="../assets/images/header/Caret_Down_SM.svg" alt="Arrow icon">
        </div>
        <?php if ($isLoggedIn): ?>
            <div class="dropdown">
                <button class="create-account account-active dropdown-toggle" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
                    <?= $user['firstname'] ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                    <li>
                        <a class="dropdown-item" href="/profile">
                            <i class="bi bi-person-circle me-2"></i> My Account
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="/auth.logout">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        <?php else: ?>
            <div class="d-flex align-items-center gap-3">
                <a href="/register" class="create-account text-decoration-none">Create a new account</a>
                <a href="/login" class="text-decoration-none login-link">Login</a>
            </div>
<!--            <a href="/register" class="create-account text-decoration-none">Create a new account</a>-->
        <?php endif; ?>

    </div>
</header>


