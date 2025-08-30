<?php

require_once __DIR__ . '/../../bootstrap.php';

loadRepo('repositories/invoice/InvoiceRepository.php');

class OrderController
{
    public function updateStatus(): void
    {
        header('Content-Type: application/json');

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $status = $_POST['status'] ?? '';
        $validStatuses = ['A', 'P', 'C'];

        if ($id <= 0 || !in_array($status, $validStatuses, true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            return;
        }

        $repo = new InvoiceRepository();
        $ok = $repo->updateStatus($id, $status);
        if ($ok) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Update failed']);
        }
    }

    public function exportOrders(): void
    {
        $searchTerm   = trim($_GET['search'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');

        $repo = new InvoiceRepository();
        $total  = $repo->countAll($searchTerm, $statusFilter);
        $orders = $repo->findPaginated(0, $total, $searchTerm, $statusFilter);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="orders.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Invoice', 'Client', 'Date', 'Total', 'Status']);
        foreach ($orders as $order) {
            fputcsv($out, [
                $order['invoice_number'],
                $order['client_name'],
                date('Y-m-d', strtotime($order['created_at'])),
                $order['total_amount'],
                $order['status']
            ]);
        }
        fclose($out);
        exit;
    }
}
