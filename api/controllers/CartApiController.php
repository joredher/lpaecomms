<?php

declare(strict_types=1);

final class CartApiController
{
    public static function show(): void
    {
        self::ensureCart();
        ApiResponder::success(['cart' => self::summariseCart()]);
    }

    public static function add(): void
    {
        $data = api_read_json_body();
        $productId = (int)($data['productId'] ?? $data['product_id'] ?? 0);
        $quantity = max(1, (int)($data['quantity'] ?? 1));

        if ($productId <= 0) {
            ApiResponder::error('productId is required.', 422, 'validation_error');
            return;
        }

        $conn = Database::getConnection();
        $stmt = $conn->prepare('SELECT * FROM lpa_stock WHERE lpa_stock_ID = :id');
        $stmt->execute([':id' => $productId]);
        $product = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$product) {
            ApiResponder::error('Product not found.', 404, 'not_found');
            return;
        }

        $purchasedStmt = $conn->prepare(
            "SELECT COALESCE(SUM(ii.lpa_invitem_qty),0) AS purchased
             FROM lpa_invoice_items ii
             JOIN lpa_invoices i ON i.lpa_invoices_ID = ii.lpa_fk_invoices_ID
             WHERE ii.lpa_fk_stock_ID = :id AND i.lpa_inv_status = 'A'"
        );
        $purchasedStmt->execute([':id' => $productId]);
        $purchased = (int)$purchasedStmt->fetchColumn();
        $available = max(0, (int)$product['lpa_stock_onhand'] - $purchased);

        if ($available <= 0) {
            ApiResponder::error('Product is out of stock.', 409, 'out_of_stock');
            return;
        }

        self::ensureCart();

        if (!isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId] = [
                'name' => $product['lpa_stock_name'],
                'price' => (float)$product['lpa_stock_price'],
                'image' => getProductImageUrl($product['lpa_stock_image'] ?? ''),
                'stock' => $available,
                'quantity' => 0,
            ];
        }

        $current = (int)$_SESSION['cart'][$productId]['quantity'];
        $newQuantity = min($available, $current + $quantity);
        $_SESSION['cart'][$productId]['quantity'] = $newQuantity;
        $_SESSION['cart'][$productId]['stock'] = $available;

        if (!isset($_SESSION['cart_created_at'])) {
            $_SESSION['cart_created_at'] = time();
        }

        ApiResponder::success([
            'cart' => self::summariseCart(),
            'message' => sprintf('Added %s to cart.', $product['lpa_stock_name']),
        ]);
    }

    private static function ensureCart(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
    }

    public static function summariseCart(): array
    {
        if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        $items = [];
        $total = 0.0;
        $count = 0;

        foreach ($_SESSION['cart'] as $id => $item) {
            $quantity = (int)($item['quantity'] ?? 0);
            $price = (float)($item['price'] ?? 0);
            $lineTotal = $price * $quantity;
            $total += $lineTotal;
            $count += $quantity;

            $items[] = [
                'productId' => (int)$id,
                'name' => $item['name'],
                'price' => $price,
                'quantity' => $quantity,
                'image' => $item['image'] ?? null,
                'lineTotal' => $lineTotal,
            ];
        }

        $createdAt = $_SESSION['cart_created_at'] ?? time();
        $expiresAt = $createdAt + 1800;
        $expiresIn = max(0, $expiresAt - time());

        return [
            'items' => $items,
            'itemCount' => $count,
            'total' => $total,
            'currency' => 'AUD',
            'createdAt' => $createdAt,
            'expiresAt' => $expiresAt,
            'expiresIn' => $expiresIn,
        ];
    }
}
