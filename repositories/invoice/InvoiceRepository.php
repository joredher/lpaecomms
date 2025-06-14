<?php
use repositories\BaseRepository;
require_once 'repositories/BaseRepository.php';

class InvoiceRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct('lpa_invoices');
    }

    /**
     * Create an invoice record
     */
    public function createInvoice(array $data): int|false
    {
        $success = $this->create([
            'lpa_inv_no' => $this->generateInvoiceNumber(),
            'lpa_inv_date'      => date('Y-m-d H:i:s'),
            'lpa_inv_amount'     => $data['total'],
            'lpa_inv_status'    => $data['status'],
            'lpa_fk_clients_ID' => $data['client_id'],
            'lpa_inv_client_address' => $data['address'],
        ]);

        return $success ? $this->conn->lastInsertId() : false;
    }

    /**
     * Add an item to the invoice (products inside the order)
     */
    public function addInvoiceItem(array $data): bool
    {
        $stmt = $this->conn->prepare(
            /** @lang text */ "INSERT INTO lpa_invoice_items (
                lpa_fk_invoices_ID,
                lpa_fk_stock_ID,
                lpa_invitem_stock_name,
                lpa_invitem_qty,
                lpa_invitem_stock_price,
                lpa_invitem_stock_amount
            ) VALUES (
                :invoice_id, :stock_id, :name, :qty, :price, :amount)");

        return $stmt->execute([
            'invoice_id' => $data['invoice_id'],
            'stock_id'   => $data['stock_id'],
            'name'       => $data['name'],
            'qty'        => $data['quantity'],
            'price'      => $data['price'],
            'amount'     => $data['amount']
        ]);
    }

    public function generateInvoiceNumber(): string {
        return "CTI-INV-" . date("YmdHis");
    }
}
