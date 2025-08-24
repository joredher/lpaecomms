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
        $user = $_SESSION['user'];
        $pageNum  = isset($_GET['page_num']) && is_numeric($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
        $pageSize = 10;
        $offset   = ($pageNum - 1) * $pageSize;

        $invoices    = $this->invoiceRepo->getInvoicesByUser($user['id'], $offset, $pageSize);
        $totalInvoices = $this->invoiceRepo->countInvoicesByUser($user['id']);
        $totalPages  = (int)ceil($totalInvoices / $pageSize);

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

        $user = $_SESSION['user'];
        $data = $this->invoiceRepo->getInvoiceWithItems($invoiceId, $user['id']);
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
