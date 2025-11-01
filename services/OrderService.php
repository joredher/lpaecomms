<?php

require_once __DIR__ . '/../bootstrap.php';

loadRepo('repositories/invoice/InvoiceRepository.php');

class OrderService
{
    private InvoiceRepository $invoiceRepo;

    public function __construct()
    {
        $this->invoiceRepo = new InvoiceRepository();
    }

    /**
     * @return array{success:bool,status:int,data?:array,message?:string}
     */
    public function listForUser(int $userId, int $limit = 5, int $offset = 0): array
    {
        $orders = $this->invoiceRepo->getInvoicesByUser($userId, $offset, $limit);
        $total = $this->invoiceRepo->countInvoicesByUser($userId);

        return [
            'success' => true,
            'status' => 200,
            'data' => [
                'items' => array_map(fn(array $row) => $this->normalizeInvoice($row), $orders),
                'limit' => $limit,
                'offset' => $offset,
                'total' => $total,
                'hasMore' => ($offset + count($orders)) < $total,
            ],
        ];
    }

    /**
     * @return array{success:bool,status:int,data?:array,message?:string}
     */
    public function getBySlug(string $slug, ?int $userId = null): array
    {
        $data = $this->invoiceRepo->getInvoiceWithItemsBySlug($slug, $userId);
        if (!$data) {
            return [
                'success' => false,
                'status' => 404,
                'message' => 'Order not found.',
            ];
        }

        return [
            'success' => true,
            'status' => 200,
            'data' => $this->normalizeInvoiceDetails($data),
        ];
    }

    /**
     * @return array{success:bool,status:int,data?:array,message?:string}
     */
    public function getById(int $id, ?int $userId = null): array
    {
        $data = $this->invoiceRepo->getInvoiceWithItems($id, $userId);
        if (!$data) {
            return [
                'success' => false,
                'status' => 404,
                'message' => 'Order not found.',
            ];
        }

        return [
            'success' => true,
            'status' => 200,
            'data' => $this->normalizeInvoiceDetails($data),
        ];
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function normalizeInvoice(array $row): array
    {
        return [
            'id' => (int)($row['id'] ?? $row['lpa_invoices_ID'] ?? 0),
            'number' => $row['invoice_number'] ?? $row['lpa_inv_no'] ?? null,
            'slug' => $row['slug'] ?? $row['lpa_inv_slug'] ?? null,
            'status' => $row['status'] ?? $row['lpa_inv_status'] ?? null,
            'total' => isset($row['total_amount']) ? (float)$row['total_amount'] : (float)($row['lpa_inv_amount'] ?? 0),
            'created_at' => $row['created_at'] ?? $row['lpa_inv_date'] ?? null,
        ];
    }

    /**
     * @param array{invoice:array,items:array,totals:array} $payload
     * @return array<string,mixed>
     */
    private function normalizeInvoiceDetails(array $payload): array
    {
        $invoice = $payload['invoice'] ?? [];
        return [
            'invoice' => $this->normalizeInvoice($invoice),
            'items' => array_map(function (array $item) {
                return [
                    'id' => (int)($item['stock_id'] ?? $item['lpa_fk_stock_ID'] ?? 0),
                    'name' => $item['name'] ?? $item['lpa_invitem_stock_name'] ?? '',
                    'quantity' => (int)($item['quantity'] ?? $item['lpa_invitem_qty'] ?? 0),
                    'price' => (float)($item['price'] ?? $item['lpa_invitem_stock_price'] ?? 0),
                    'amount' => (float)($item['amount'] ?? $item['lpa_invitem_stock_amount'] ?? 0),
                ];
            }, $payload['items'] ?? []),
            'totals' => $payload['totals'] ?? [],
        ];
    }
}
