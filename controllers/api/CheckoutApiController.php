<?php

require_once __DIR__ . '/BaseApiController.php';
require_once __DIR__ . '/../../services/CheckoutService.php';

class CheckoutApiController extends BaseApiController
{
    private CheckoutService $checkoutService;

    public function __construct()
    {
        $this->checkoutService = new CheckoutService();
    }

    public function process(): void
    {
        if ($auth = $this->requireAuth()) {
            $this->respond($auth);
            return;
        }

        $data = $this->readInput();
        $result = $this->checkoutService->process($data);
        $this->respond($result);
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
