<?php

require_once 'repositories/invoice/InvoiceRepository.php';

class InvoiceWorkflow
{
    private InvoiceRepository $invoiceRepo;

    public function __construct()
    {
        $this->invoiceRepo = new InvoiceRepository();
    }

    /**
     * Create an invoice, persist items and set initial status.
     */
    public function handle(array $invoiceData, array $cart): int
    {
        // Determine initial status based on payment method
        $method = strtolower((string)($invoiceData['payment_method'] ?? 'card'));
        // If cash on delivery, keep it Pending; if card, activate immediately
        $invoiceData['status'] = $invoiceData['status'] ?? ($method === 'cod' ? 'P' : 'A');

        $invoiceId = $this->invoiceRepo->createInvoice($invoiceData);
        if (!$invoiceId) {
            return 0;
        }

        foreach ($cart as $stockId => $item) {
            $this->invoiceRepo->addInvoiceItem([
                'invoice_id' => $invoiceId,
                'stock_id' => (int)$stockId,
                'name' => $item['name'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'amount' => $item['price'] * $item['quantity'],
            ]);
        }

        // For card method, ensure invoice is marked Active
        if ($method !== 'cod') {
            $this->invoiceRepo->updateStatus($invoiceId, 'A');
        }

        return $invoiceId;
    }
}
