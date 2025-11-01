<?php

require_once __DIR__ . '/BaseApiController.php';
require_once __DIR__ . '/../../services/OrderService.php';

class OrderApiController extends BaseApiController
{
    private OrderService $orderService;

    public function __construct()
    {
        $this->orderService = new OrderService();
    }

    public function index(): void
    {
        if ($guard = $this->requireCustomer()) {
            $this->respond($guard);
            return;
        }
        $userId = (int)$_SESSION['user']['id'];
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        $result = $this->orderService->listForUser($userId, $limit, $offset);
        $this->respond($result);
    }

    public function show(string $identifier): void
    {
        if ($guard = $this->requireCustomer()) {
            $this->respond($guard);
            return;
        }
        $userId = (int)$_SESSION['user']['id'];
        $result = is_numeric($identifier)
            ? $this->orderService->getById((int)$identifier, $userId)
            : $this->orderService->getBySlug($identifier, $userId);
        $this->respond($result);
    }
}
