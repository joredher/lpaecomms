<?php
// router.php
//use controllers\auth\AuthController;
use controllers\RegisterController;

require_once __DIR__ . '/../bootstrap.php';
//use controllers\cart\CartController;
require_once 'controllers/RegisterController.php';
//require_once 'controllers/cart/CartController.php';
require_once 'controllers/auth/AuthController.php';
require_once 'controllers/checkout/CheckoutController.php';
require_once 'controllers/contact/ContactController.php';
require_once 'controllers/orders/OrderController.php';
loadRepo('middleware/AuthMiddleware.php');
loadRepo('services/AddressService.php');


$route = $_GET['route'] ?? trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
if ($route === ''): $route = 'home'; endif;
$path = 'pages/';

RegisterController::register($route, 'auth', 'AuthController', ['login', 'register', 'logout', 'forgot', 'resetPassword']);
RegisterController::register($route, 'profile', 'ProfileController', ['create', 'store']);
RegisterController::register($route, 'cart', 'CartController', ['add', 'remove', 'update', 'applyCoupon']);
RegisterController::register($route, 'checkout', 'CheckoutController', ['process', 'confirmation']);
RegisterController::register($route, 'contact', 'ContactController', ['send', 'capture']);
RegisterController::register($route, 'orders', 'OrderController', ['index', 'show']);

// --- Helpers
$ua    = $_SERVER['HTTP_USER_AGENT'] ?? '';
$isBot = (bool) preg_match('/bot|crawl|spider|slurp|facebookexternalhit|preview/i', $ua);


// --- Welcome actions (triggered by the two buttons on the welcome page)
if (isset($_GET['welcome'])) {
    $opt = $_GET['welcome'];

    $cookieOpts = [
        'expires'  => time() + 365 * 24 * 60 * 60, // 1 year
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ];

    if ($opt === 'login') {
        // LOG ON => consent + first_visit, then go to /login
        setcookie('lpa_cookie_consent', '1', $cookieOpts);
        setcookie('lpa_first_visit',    '1', $cookieOpts);
        header('Location: /login');
        exit;
    }

    if ($opt === 'go') {
        // GO AHEAD => only first_visit, then go home
        setcookie('lpa_first_visit', '1', $cookieOpts);
        header('Location: /home');
        exit;
    }
}


// --- Gate: show welcome only if user hasn't seen it yet and it's not a bot
$firstVisitDone = isset($_COOKIE['lpa_first_visit']);

switch ($route) {
    case 'home':
        if (!$isBot && !$firstVisitDone) {
            $title = 'Welcome';
            $pageContent = $path . 'first_visit_content.php';
            include 'includes/layout_auth.php';
            break;
        }

        $pageContent = $path . 'home.php';
        include 'includes/layout.php';
        break;
    case 'products':
        $isAjaxReq = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if ($isAjaxReq) {
            include $path . 'products.php';
        } else {
            $pageContent = $path . 'products.php';
            include 'includes/layout.php';
        }
        break;
    case 'search.products':
        $title = 'Search Results';
        $pageContent = $path . 'search_engine_template.php';
        include 'includes/layout.php';
        break;
    case 'about':
        $pageContent = $path . 'about.php';
        include 'includes/layout.php';
        break;
    case 'contact':
        $pageContent = $path . 'contact.php';
        include 'includes/layout.php';
        break;
    case 'product':
        if (isset($_GET['id']) && is_numeric($_GET['id'])) {
            $productId = (int)$_GET['id'];

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
        if (empty($_SESSION['cart'])):
            header('Location: /products');
        else:
            $pageContent = $path . 'client/view_cart.php';
            include 'includes/layout.php';
        endif;
        break;
    case 'checkout':
        $controller = new CheckoutController();
        $controller->start();
        break;
    case 'orders':
        $controller = new OrderController();
        $controller->index();
        break;
    case 'profile':
        AuthMiddleware::authOnly();
        $pageContent = $path . '/user/account.php';
        include 'includes/layout.php';
        break;
    case 'address':
        AuthMiddleware::authOnly();
        $_GET['address'] = $_GET['address'] ?? null;

        $response = ['success' => false, 'message' => 'No address provided.'];

        if (empty($_GET['address'])) {
            echo json_encode($response, JSON_THROW_ON_ERROR);
            break;
        }

        $service = new AddressService();
        $result = $service->getStructuredAddress($_GET['address']);

        $response = ['success' => true, 'address' => $result];

        echo json_encode($response);
        break;
    case 'register':
    case 'login':
    case 'forgot_password':
    case 'reset_password':
        AuthMiddleware::guestOnly();
        $title = match ($route) {
            'login' => 'Login',
            'register' => 'Register',
            'forgot_password' => 'Forgot Password',
            'reset_password' => 'Reset Password',
            default => 'home'
        };

        if ($route === 'reset_password' && !isset($_GET['key_rpu'])):
            $_SESSION['flash_message'] = [
                'message' => 'The link was accessed incorrectly.',
                'type' => 'warning'
            ];

            header('Location: /login');
            exit();
        endif;


//        if ($route === 'register' && isset($_SESSION['pending_user_id'])) {
//            unset($_SESSION['pending_user_id']);
//        }

        $pageContent = $path . 'auth/' . $route . '.php';
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
        $pageContent = $path . 'error/404.php';
        include 'includes/layout.php';
        break;
}
