<?php
//namespace controllers\cart;

require_once 'includes/config.php'; // ✅ Load config first (DB, constants, etc.)
require_once 'middleware/AuthMiddleware.php';
require_once 'services/CartService.php';
class CartController
{
    private CartService $cartService;

    public function __construct() {
        $this->cartService = new CartService();
    }

    public function add()
    {
        if (!AuthMiddleware::userOnly()) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'status' => 403,
                'error' => 'Only customer accounts may manage the cart.',
            ]);
            exit;
        }

        header('Content-Type: application/json');
        $productId = (int)($_POST['productId'] ?? 0);
        if ($productId <= 0) {
            echo json_encode(['success' => false, 'error' => 'No productId sent']);
            exit;
        }

        $result = $this->cartService->addItem($productId);
        echo json_encode([
            'success' => $result['success'],
            'cartCount' => $result['data']['cartCount'] ?? 0,
            'message' => $result['message'] ?? null,
        ]);
        exit;
    }

    public function remove() {
        if (!AuthMiddleware::userOnly()) {
            AuthMiddleware::abort403('Only customer accounts may manage the cart.');
        }
        if (!isset($_GET['id'])) return;

        $id = (int) $_GET['id'];
        $this->cartService->removeItem($id);

        header('Location: ?route=cart');
        exit;
    }

    public function update() {
        if (!AuthMiddleware::userOnly()) {
            AuthMiddleware::abort403('Only customer accounts may manage the cart.');
        }
        $ids = $_POST['id'] ?? [];
        $quantities = $_POST['quantity'] ?? [];

        $items = [];
        foreach ($ids as $index => $id) {
            $items[(int)$id] = $quantities[$index] ?? 1;
        }

        $this->cartService->updateQuantities($items);

        $_SESSION['flash_message'] = [
            'message' => 'Cart updated successfully',
            'type' => 'success'
        ];

        header('Location: /cart');
        exit;
    }

    public function applyCoupon() {
        if (!AuthMiddleware::userOnly()) {
            AuthMiddleware::abort403('Only customer accounts may manage the cart.');
        }
        $coupon = $_POST['coupon_code'] ?? '';
        $this->cartService->applyCoupon($coupon);

        header('Location: /cart');
        exit;
    }


}