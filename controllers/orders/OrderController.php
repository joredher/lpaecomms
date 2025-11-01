<?php

require_once __DIR__ . '/../../bootstrap.php';

loadRepo('repositories/invoice/InvoiceRepository.php');
loadRepo('middleware/AuthMiddleware.php');
require_once 'services/OrderService.php';

class OrderController
{
    private InvoiceRepository $invoiceRepo;
    private OrderService $orderService;

    public function __construct()
    {
        AuthMiddleware::authOnly();
        $this->invoiceRepo = new InvoiceRepository();
        $this->orderService = new OrderService();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function index(): void
    {
        $user   = $_SESSION['user'];
        $limit  = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : 5;
        $offset = isset($_GET['offset']) && is_numeric($_GET['offset']) ? (int)$_GET['offset'] : 0;

        $result = $this->orderService->listForUser($user['id'], $limit, $offset);

        if (isset($_GET['offset'])) {
            header('Content-Type: application/json');
            echo json_encode([
                'invoices' => $result['data']['items'] ?? [],
                'hasMore' => $result['data']['hasMore'] ?? false,
            ]);
            return;
        }

        $invoices      = $result['data']['items'] ?? [];
        $totalInvoices = $result['data']['total'] ?? 0;
        $hasMore       = $result['data']['hasMore'] ?? false;

        $pageContent = 'pages/user/profile_orders.php';
        include 'includes/layout.php';
    }

    public function show(): void
    {
        $slug = $_GET['slug'] ?? '';
        $user   = $_SESSION['user'];
        $userId = (isset($user['group']) && (int)$user['group'] === 1) ? null : $user['id'];

        $data = null;
        if ($slug === '' && isset($_GET['id'])) {
            $invoiceId = (int)$_GET['id'];
            if ($invoiceId > 0) {
                $result = $this->orderService->getById($invoiceId, $userId);
                if ($result['success']) {
                    $data = $result['data'];
                    $slug = $data['invoice']['slug'] ?? $slug;
                    if (!empty($slug)) {
                        header('Location: /orders.show?slug=' . urlencode($slug));
                        return;
                    }
                }
            }
        }

        if ($data === null) {
            if ($slug === '') {
                header('Location: /orders');
                return;
            }
            $result = $this->orderService->getBySlug($slug, $userId);
            if (!$result['success']) {
                header('Location: /orders');
                return;
            }
            $data = $result['data'];
        }

        $invoice = $data['invoice'] ?? [];
        $items = $data['items'] ?? [];
        $totals = $data['totals'] ?? [];

        $pageContent = 'pages/client/confirmation.php';
        include 'includes/layout.php';
    }
}
