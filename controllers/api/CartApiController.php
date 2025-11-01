<?php

require_once __DIR__ . '/BaseApiController.php';
require_once __DIR__ . '/../../services/CartService.php';

class CartApiController extends BaseApiController
{
    private CartService $cartService;

    public function __construct()
    {
        $this->cartService = new CartService();
    }

    public function summary(): void
    {
        $this->respond($this->cartService->getCart());
    }

    public function add(): void
    {
        $data = $this->readInput();
        $productId = (int)($data['productId'] ?? $data['product_id'] ?? 0);
        if ($productId <= 0) {
            $this->respond([
                'success' => false,
                'status' => 422,
                'message' => 'productId is required.',
            ]);
            return;
        }
        $this->respond($this->cartService->addItem($productId));
    }

    public function update(): void
    {
        $data = $this->readInput();
        $items = $data['items'] ?? [];
        if (!is_array($items)) {
            $this->respond([
                'success' => false,
                'status' => 422,
                'message' => 'items payload must be an object of productId => quantity.',
            ]);
            return;
        }
        $this->respond($this->cartService->updateQuantities($items));
    }

    public function remove(int $productId): void
    {
        $this->respond($this->cartService->removeItem($productId));
    }

    public function applyCoupon(): void
    {
        $data = $this->readInput();
        $code = (string)($data['coupon'] ?? $data['coupon_code'] ?? '');
        $this->respond($this->cartService->applyCoupon($code));
    }

    /**
     * @return array<string,mixed>
     */
    private function readInput(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            $body = file_get_contents('php://input');
            if ($body !== false && $body !== '') {
                $decoded = json_decode($body, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return array_merge($decoded, $_POST);
                }
            }
        }
        return $_POST;
    }
}
