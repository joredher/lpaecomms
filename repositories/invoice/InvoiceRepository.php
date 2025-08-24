<?php
use repositories\BaseRepository;
loadRepo('repositories/BaseRepository.php');

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
            'lpa_inv_client_name' => $data['client_name'],
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

    /**
     * Update the status of an invoice (e.g. Pending -> Active)
     */
    public function updateStatus(int $invoiceId, string $status): bool
    {
        $stmt = $this->conn->prepare(
            /** @lang text */ "UPDATE lpa_invoices SET lpa_inv_status = :status WHERE lpa_invoices_ID = :id"
        );

        return $stmt->execute([
            ':status' => $status,
            ':id' => $invoiceId,
        ]);
    }

    public function generateInvoiceNumber(): string {
        return "CTI-INV-" . date("YmdHis");
    }

    public function getInvoicesByUser(int $userId, ?int $offset = null, ?int $limit = null): array
    {
        $q = /** @lang text */
            "SELECT i.lpa_invoices_ID AS id,
                   i.lpa_inv_no       AS invoice_number,
                   i.lpa_inv_date     AS created_at,
                   i.lpa_inv_status   AS status,
                   i.lpa_inv_amount   AS total_amount
            FROM lpa_invoices i
            JOIN lpa_clients c ON c.lpa_clients_ID = i.lpa_fk_clients_ID
            WHERE c.lpa_clients_fk_user_id = :user_id
            ORDER BY i.lpa_invoices_ID DESC";

        if ($offset !== null && $limit !== null) {
            $q .= " LIMIT :offset, :limit";
        }

        $stmt = $this->conn->prepare($q);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        if ($offset !== null && $limit !== null) {
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countInvoicesByUser(int $userId): int
    {
        $stmt = $this->conn->prepare(
            /** @lang text */ "SELECT COUNT(*)
             FROM lpa_invoices i
             JOIN lpa_clients c ON c.lpa_clients_ID = i.lpa_fk_clients_ID
             WHERE c.lpa_clients_fk_user_id = :user_id"
        );
        $stmt->execute([':user_id' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    public function getInvoiceWithItems(int $invoiceId, int $userId): ?array
    {
        // 1) Invoice (scoped)
        $q1 = /** @lang text */
            "SELECT i.lpa_invoices_ID      AS id,
               i.lpa_inv_no           AS invoice_number,
               i.lpa_inv_date         AS created_at,
               i.lpa_inv_status       AS status,
               i.lpa_inv_amount       AS total_amount,
               i.lpa_inv_client_name  AS client_name,
               i.lpa_inv_client_address AS client_address,
               c.lpa_clients_firstname,
               c.lpa_clients_lastname,
               c.lpa_client_email AS client_email,
               c.lpa_client_phone AS client_phone
        FROM lpa_invoices i
        JOIN lpa_clients c ON c.lpa_clients_ID = i.lpa_fk_clients_ID
        WHERE i.lpa_invoices_ID = :invoice_id
          AND c.lpa_clients_fk_user_id = :user_id
        LIMIT 1";
        $stmt = $this->conn->prepare($q1);
        $stmt->execute([
            ':invoice_id' => $invoiceId,
            ':user_id'    => $userId
        ]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$invoice) return null;

        // 2) Items
        $q2 = /** @lang text */
            "SELECT ii.lpa_invoice_items_ID AS id,
                   ii.lpa_invitem_stock_name AS name,
                   ii.lpa_invitem_qty        AS quantity,
                   ii.lpa_invitem_stock_price AS unit_price,
                   ii.lpa_invitem_stock_amount AS total_price,
                   s.lpa_stock_ID          AS sku,
                   s.lpa_stock_image       AS image
            FROM lpa_invoice_items ii
            JOIN lpa_stock s ON s.lpa_stock_ID = ii.lpa_fk_stock_ID
            WHERE ii.lpa_fk_invoices_ID = :invoice_id
            ORDER BY ii.lpa_invoice_items_ID ASC";
        $stmt = $this->conn->prepare($q2);
        $stmt->execute([':invoice_id' => $invoiceId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3) Totals (simple)
        $totals = [
            'total'    => (float)($invoice['total_amount'] ?? 0),
            'currency' => 'AUD'
        ];

        return [
            'invoice' => $invoice,
            'items'   => $items,
            'totals'  => $totals
        ];
    }
}
