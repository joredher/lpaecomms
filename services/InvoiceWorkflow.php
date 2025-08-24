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
     * Create an invoice, persist items and mark it as paid.
     */
    public function handle(array $invoiceData, array $cart): int
    {
        // Start with a pending invoice
        $invoiceData['status'] = $invoiceData['status'] ?? 'P';
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

        // Payment succeeded: activate invoice
        $this->invoiceRepo->updateStatus($invoiceId, 'A');

        return $invoiceId;
    }
}
