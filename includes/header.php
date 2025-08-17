<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = isset($_SESSION['user']);

$user = $_SESSION['user'] ?? null;

$uri = $_SERVER['REQUEST_URI'];

if (!str_contains(strtolower($uri), '/cart')) {
    $_SESSION['previous_page'] = $uri ?? 'home';
}



$style = 'text-decoration-none';
if (in_array(strtolower($uri), ['/home', '/products', '/about', '/contact'])) {
    $style = 'text-color-selection text-decoration-underline';
}



?>
<header class="header container-fluid d-flex flex-column flex-md-row align-items-center justify-content-between gap-3 py-3">
    <div class="logo-nav d-flex align-items-center gap-3 flex-column flex-md-row">
        <a href="/home" class="text-decoration-none">
            <img class="logo img-fluid" src="../assets/images/Logo.svg" alt="Logo">
        </a>
        <nav class="navigation d-flex flex-column flex-md-row align-items-center gap-3">
            <a href="/home" class="<?= strtolower($uri) === '/home' ? $style : '' ?>">Home</a>
            <a href="/products" class="<?= strtolower($uri) === '/products' ? $style : '' ?>">Products</a>
            <a href="/about" class="<?= strtolower($uri) === '/about' ? $style : '' ?>">About</a>
            <a href="/contact" class="<?= strtolower($uri) === '/contact' ? $style : '' ?>">Contact</a>
        </nav>
    </div>
    <div class="actions d-flex flex-column flex-md-row align-items-center gap-3 w-100 w-md-auto">
        <div class="search-box d-flex w-100 w-md-auto">
            <img class="img-fluid" src="../assets/images/header/u_search.svg" alt="Search icon">
            <label class="flex-grow-1">
                <input class="form-control" type="text" name="search" placeholder="Search something here!">
            </label>
        </div>
        <div class="cart-icon">
            <a href="/cart" class="position-relative ms-md-3 text-decoration-none">
                <img class="img-fluid" src="../assets/images/header/u_cart.svg" alt="Cart icon">
<!--                --><?php //if (!empty($_SESSION['cart'])): ?>
                    <span id="cart-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">
                        <?= array_sum(array_column(@$_SESSION['cart'] ?? [], 'quantity')); ?>
                    </span>
<!--                --><?php //endif; ?>
            </a>
        </div>
        <div class="currency d-flex align-items-center gap-1">
            <img class="img-fluid" src="../assets/images/header/Australia.svg" alt="Australia flag">
            <span>AUD</span>
            <img class="img-fluid" src="../assets/images/header/Caret_Down_SM.svg" alt="Arrow icon">
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
            <div class="d-flex align-items-center gap-3 flex-column flex-md-row w-100 w-md-auto">
                <a href="/register" class="create-account text-decoration-none w-100 w-md-auto text-center">Create a new account</a>
                <a href="/login" class="text-decoration-none login-link w-100 w-md-auto text-center">Login</a>
            </div>
<!--            <a href="/register" class="create-account text-decoration-none">Create a new account</a>-->
        <?php endif; ?>

    </div>
</header>
