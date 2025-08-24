<?php
//namespace controllers\cart;

require_once 'includes/config.php'; // ✅ Load config first (DB, constants, etc.)

header('Content-Type: application/json');
class CartController
{
    private PDO $conn;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Ensure cart exists
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        // Load database connection once
        $this->conn = Database::getConnection();
    }

    public function add()
    {
        header('Content-Type: application/json');

        if (!isset($_POST['productId'])):
            echo json_encode(['success' => false, 'error' => 'No productId sent']);
            exit;
        endif;

        $productId = (int) $_POST['productId'];

        $stmt = $this->conn->prepare(/** @lang text */ "SELECT * FROM lpa_stock WHERE lpa_stock_ID = :id");
        $stmt->execute(['id' => $productId]);
        $product = $stmt->fetch();

        // Determine how many units have been sold for this product
        $purchasedStmt = $this->conn->prepare(
            /** @lang text */ "SELECT COALESCE(SUM(ii.lpa_invitem_qty),0) AS purchased
            FROM lpa_invoice_items ii
            JOIN lpa_invoices i ON i.lpa_invoices_ID = ii.lpa_fk_invoices_ID
            WHERE ii.lpa_fk_stock_ID = :id AND i.lpa_inv_status = 'A'"
        );
        $purchasedStmt->execute([':id' => $productId]);
        $purchased = (int) $purchasedStmt->fetchColumn();
        $available = (int) $product['lpa_stock_onhand'] - $purchased;

        if ($product && $available > 0) {
            if (isset($_SESSION['cart'][$productId])) {
                if ($_SESSION['cart'][$productId]['quantity'] < $available) {
                    $_SESSION['cart'][$productId]['quantity']++;
                }
            } else {
                $_SESSION['cart'][$productId] = [
                    'name' => $product['lpa_stock_name'],
                    'price' => $product['lpa_stock_price'],
                    'image' => 'assets/images/test-images/' . htmlspecialchars($product['lpa_stock_image']),
                    'stock' => $available,
                    'quantity' => 1,
                ];

                if (!isset($_SESSION['cart_created_at'])) {
                    $_SESSION['cart_created_at'] = time(); // Current Unix timestamp
                }
            }

            $cartCount = array_sum(array_column($_SESSION['cart'], 'quantity'));

            $response = [
                'success' => true,
                'cartCount' => $cartCount,
                'productName' => $product['lpa_stock_name'],
            ];
        } else {
            $response = ['success' => false, 'error' => 'Product not found or unavailable'];
        }

        echo json_encode($response);
        exit;
    }

    public function remove() {
        if (!isset($_GET['id'])) return;

        $id = (int) $_GET['id'];
        unset($_SESSION['cart'][$id]);

        header('Location: ?route=cart');
        exit;
    }

    public function update() {
        $ids = $_POST['id'] ?? [];
        $quantities = $_POST['quantity'] ?? [];

        foreach ($ids as $index => $id) {
            $id = (int) $id;
            $qty = max(1, (int) $quantities[$index]);

            if (isset($_SESSION['cart'][$id])) {
                $stock = $_SESSION['cart'][$id]['stock'] ?? 10;
                $_SESSION['cart'][$id]['quantity'] = min($qty, $stock); // 🔒 Evita pasar el límite
            }
        }

        $_SESSION['flash_message'] = [
            'message' => 'Cart updated successfully',
            'type' => 'success'
        ];

        header('Location: /cart');
        exit;
    }

    public function applyCoupon() {
        $coupon = $_POST['coupon_code'] ?? '';

        if (strtolower(trim($coupon)) === 'descuento10') {
            $_SESSION['discount'] = 0.1;
        } else {
            $_SESSION['discount'] = 0;
        }

        header('Location: /cart');
        exit;
    }


}