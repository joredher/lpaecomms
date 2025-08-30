<?php

require_once __DIR__ . '/../../bootstrap.php';

loadRepo('repositories/invoice/InvoiceRepository.php');
loadRepo('middleware/AuthMiddleware.php');

class OrderController
{
    private InvoiceRepository $invoiceRepo;

    public function __construct()
    {
        AuthMiddleware::authOnly();
        $this->invoiceRepo = new InvoiceRepository();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function index(): void
    {
        $user   = $_SESSION['user'];
        $limit  = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : 5;
        $offset = isset($_GET['offset']) && is_numeric($_GET['offset']) ? (int)$_GET['offset'] : 0;

        // Handle AJAX request for additional invoices
        if (isset($_GET['offset'])) {
            $invoices       = $this->invoiceRepo->getInvoicesByUser($user['id'], $offset, $limit);
            $totalInvoices  = $this->invoiceRepo->countInvoicesByUser($user['id']);
            $hasMore        = ($offset + count($invoices)) < $totalInvoices;

            header('Content-Type: application/json');
            echo json_encode(['invoices' => $invoices, 'hasMore' => $hasMore]);
            return;
        }

        $invoices      = $this->invoiceRepo->getInvoicesByUser($user['id'], 0, $limit);
        $totalInvoices = $this->invoiceRepo->countInvoicesByUser($user['id']);
        $hasMore       = $limit < $totalInvoices;

        $pageContent = 'pages/user/profile_orders.php';
        include 'includes/layout.php';
    }

    public function show(): void
    {
        $invoiceId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($invoiceId <= 0) {
            header('Location: /orders');
            return;
        }

        $user   = $_SESSION['user'];
        $userId = (isset($user['group']) && (int)$user['group'] === 1) ? null : $user['id'];
        $data   = $this->invoiceRepo->getInvoiceWithItems($invoiceId, $userId);
        if (!$data) {
            header('Location: /orders');
            return;
        }

        $invoice = $data['invoice'];
        $items = $data['items'];
        $totals = $data['totals'];

        $pageContent = 'pages/client/confirmation.php';
        include 'includes/layout.php';
    }
}
