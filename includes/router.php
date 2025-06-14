<?php
// router.php
//use controllers\auth\AuthController;
use controllers\RegisterController;
//use controllers\cart\CartController;
require_once 'controllers/RegisterController.php';
//require_once 'controllers/cart/CartController.php';
require_once 'controllers/auth/AuthController.php';
require_once 'controllers/cart/CheckoutController.php';

$route = $_GET['route'] ?? trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
if ($route === ''): $route = 'home'; endif;
$path = 'pages/';
RegisterController::register($route, 'auth', 'AuthController', ['login', 'register', 'logout']);
RegisterController::register($route, 'profile', 'ProfileController', ['create']);
RegisterController::register($route, 'cart', 'CartController', ['add', 'remove', 'update', 'applyCoupon']);
RegisterController::register($route, 'cart', 'CheckoutController', ['process']);

switch ($route) {
    case 'home':
        $pageContent = $path.'home.php';
        include 'includes/layout.php';
        break;

    case 'products':
//        include 'controllers/products_controller.php';
        $pageContent = $path.'products.php';
        include 'includes/layout.php';
        break;

    case 'product':
        if (isset($_GET['id']) && is_numeric($_GET['id'])) {
            $productId = (int) $_GET['id'];

            // Optionally pass the ID to the page
            $title = 'Product Details';
            $pageContent = 'pages/product.php';
        } else {
            // If no ID provided, show error or redirect
            $title = 'Product Not Found';
            $pageContent = 'pages/client/404.php';
        }
        include 'includes/layout.php';
        break;
    case 'cart':
        $pageContent = $path.'client/view_cart.php';
        include 'includes/layout.php';
        break;
    case 'checkout':
        $controller = new CheckoutController();
        $controller->start();
        break;
    case 'register':
    case 'login':
    case 'forgot':
        $title = $route === 'register' ? 'Register' : 'Login';

//        if ($route === 'register' && isset($_SESSION['pending_user_id'])) {
//            unset($_SESSION['pending_user_id']);
//        }

        $pageContent = $path.'auth/' . $route . '.php';
        include 'includes/layout_auth.php';
        break;
    case 'verify_email':
        $title = 'Register';
        if (!isset($_SESSION['email_verification_shown'])) {
            $_SESSION['email_verification_shown'] = true;
            $pageContent = $path . 'auth/verify_email.php';
            include 'includes/layout_auth.php';
        } else {
            unset($_SESSION['email_verification_shown']);
            header('Location: /register');
            exit;
        }
        break;
    case 'verify':
        $authController = new AuthController();
        $authController->verifyEmail();
        break;
    case 'verify-code':
        $authController = new AuthController();
        $authController->verifyCode();
        break;
    default:
        include 'pages/404.php';
        break;
}
