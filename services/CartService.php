<?php

require_once __DIR__ . '/../bootstrap.php';

class CartService
{
    private PDO $conn;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        $this->conn = Database::getConnection();
    }

    /**
     * @return array{success:bool,status:int,message:string,data?:array}
     */
    public function addItem(int $productId): array
    {
        $product = $this->fetchProduct($productId);
        if (!$product) {
            return [
                'success' => false,
                'status' => 404,
                'message' => 'Product not found or unavailable.',
            ];
        }

        $available = $product['available'];
        if ($available <= 0) {
            return [
                'success' => false,
                'status' => 409,
                'message' => 'Product is out of stock.',
            ];
        }

        if (isset($_SESSION['cart'][$productId])) {
            $currentQty = $_SESSION['cart'][$productId]['quantity'];
            if ($currentQty < $available) {
                $_SESSION['cart'][$productId]['quantity'] = $currentQty + 1;
            } else {
                return [
                    'success' => false,
                    'status' => 409,
                    'message' => 'No more stock available for this product.',
                ];
            }
        } else {
            $_SESSION['cart'][$productId] = [
                'name' => $product['lpa_stock_name'],
                'price' => (float)$product['lpa_stock_price'],
                'image' => getProductImageUrl($product['lpa_stock_image'] ?? ''),
                'stock' => $available,
                'quantity' => 1,
            ];
            if (!isset($_SESSION['cart_created_at'])) {
                $_SESSION['cart_created_at'] = time();
            }
        }

        $cartCount = $this->getCartCount();

        return [
            'success' => true,
            'status' => 200,
            'message' => 'Product added to cart.',
            'data' => [
                'cartCount' => $cartCount,
                'cart' => $_SESSION['cart'],
            ],
        ];
    }

    /**
     * @return array{success:bool,status:int,message:string,data?:array}
     */
    public function removeItem(int $productId): array
    {
        if (!isset($_SESSION['cart'][$productId])) {
            return [
                'success' => false,
                'status' => 404,
                'message' => 'Item is not in the cart.',
            ];
        }

        unset($_SESSION['cart'][$productId]);

        return [
            'success' => true,
            'status' => 200,
            'message' => 'Item removed from cart.',
            'data' => [
                'cartCount' => $this->getCartCount(),
                'cart' => $_SESSION['cart'],
            ],
        ];
    }

    /**
     * @param array<int,int> $updates
     * @return array{success:bool,status:int,message:string,data?:array}
     */
    public function updateQuantities(array $updates): array
    {
        foreach ($updates as $productId => $qty) {
            $productId = (int)$productId;
            $qty = max(1, (int)$qty);
            if (!isset($_SESSION['cart'][$productId])) {
                continue;
            }
            $stock = $_SESSION['cart'][$productId]['stock'] ?? 10;
            $_SESSION['cart'][$productId]['quantity'] = min($qty, $stock);
        }

        return [
            'success' => true,
            'status' => 200,
            'message' => 'Cart updated successfully.',
            'data' => [
                'cartCount' => $this->getCartCount(),
                'cart' => $_SESSION['cart'],
            ],
        ];
    }

    /**
     * @return array{success:bool,status:int,message:string,data?:array}
     */
    public function applyCoupon(string $code): array
    {
        $code = strtolower(trim($code));
        $_SESSION['discount'] = $code === 'descuento10' ? 0.1 : 0.0;

        return [
            'success' => true,
            'status' => 200,
            'message' => $code === 'descuento10'
                ? 'Coupon applied successfully.'
                : 'Coupon removed.',
            'data' => [
                'discount' => $_SESSION['discount'],
            ],
        ];
    }

    /**
     * @return array{success:bool,status:int,data:array}
     */
    public function getCart(): array
    {
        return [
            'success' => true,
            'status' => 200,
            'data' => [
                'cart' => $_SESSION['cart'],
                'cartCount' => $this->getCartCount(),
                'discount' => $_SESSION['discount'] ?? 0,
            ],
        ];
    }

    private function getCartCount(): int
    {
        return (int)array_sum(array_column($_SESSION['cart'], 'quantity'));
    }

    /**
     * @return array<string,mixed>|null
     */
    private function fetchProduct(int $productId): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT s.*, s.lpa_stock_onhand - COALESCE(p.purchased, 0) AS available
             FROM lpa_stock s
             LEFT JOIN (
                 SELECT ii.lpa_fk_stock_ID, SUM(ii.lpa_invitem_qty) AS purchased
                 FROM lpa_invoice_items ii
                 JOIN lpa_invoices i ON i.lpa_invoices_ID = ii.lpa_fk_invoices_ID AND i.lpa_inv_status = 'A'
                 GROUP BY ii.lpa_fk_stock_ID
             ) p ON p.lpa_fk_stock_ID = s.lpa_stock_ID
             WHERE s.lpa_stock_ID = :id LIMIT 1"
        );
        $stmt->execute([':id' => $productId]);
        $product = $stmt->fetch();
        if (!$product) {
            return null;
        }

        $product['available'] = (int)$product['available'];
        return $product;
    }
}
