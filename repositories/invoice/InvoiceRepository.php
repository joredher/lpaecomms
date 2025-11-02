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
        $invNo = $this->generateInvoiceNumber();
        $success = $this->create([
            'lpa_inv_no'          => $invNo,
            'lpa_inv_slug'        => $this->slugify($invNo),
            'lpa_inv_date'        => date('Y-m-d H:i:s'),
            'lpa_inv_amount'      => $data['total'],
            'lpa_inv_client_name' => $data['client_name'],
            'lpa_inv_status'      => $data['status'],
            'lpa_fk_clients_ID'   => $data['client_id'],
            'lpa_inv_client_address' => $data['address'],
            // new payment meta (columns created in DB)
            'lpa_inv_payment_method' => $data['payment_method'] ?? 'card',
            'lpa_inv_card_brand'     => $data['card_brand'] ?? null,
            'lpa_inv_card_last4'     => $data['card_last4'] ?? null,
            'lpa_inv_save_info'      => (int)($data['save_info'] ?? 0),
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

    private function slugify(string $text): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text), '-'));
        return $slug !== '' ? $slug : uniqid('inv-');
    }

    private function ensureSlug(int $invoiceId, string $invoiceNo): string
    {
        $stmt = $this->conn->prepare(/** @lang text */ "SELECT lpa_inv_slug FROM {$this->table} WHERE lpa_invoices_ID = :id");
        $stmt->execute([':id' => $invoiceId]);
        $slug = $stmt->fetchColumn();
        if ($slug) {
            return $slug;
        }
        $slug = $this->slugify($invoiceNo);
        $upd = $this->conn->prepare(/** @lang text */ "UPDATE {$this->table} SET lpa_inv_slug = :slug WHERE lpa_invoices_ID = :id");
        $upd->execute([':slug' => $slug, ':id' => $invoiceId]);
        return $slug;
    }

    public function getInvoicesByUser(int $userId, ?int $offset = null, ?int $limit = null): array
    {
        $q = /** @lang text */
            "SELECT i.lpa_invoices_ID AS id,
                   i.lpa_inv_no       AS invoice_number,
                   i.lpa_inv_slug     AS slug,
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

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['slug'] = $this->ensureSlug((int)$row['id'], $row['invoice_number']);
        }
        return $rows;
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

    public function countAll(string $term = '', string $status = ''): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} i JOIN lpa_clients c ON c.lpa_clients_ID = i.lpa_fk_clients_ID";
        $conditions = [];
        $params = [];

        if ($term !== '') {
            $conditions[] = "(i.lpa_inv_no LIKE :term1 OR i.lpa_inv_client_name LIKE :term2 OR CONCAT(c.lpa_clients_firstname, ' ', c.lpa_clients_lastname) LIKE :term3)";
            $params[':term1'] = "%{$term}%";
            $params[':term2'] = "%{$term}%";
            $params[':term3'] = "%{$term}%";
        }

        if ($status !== '') {
            $conditions[] = "i.lpa_inv_status = :status";
            $params[':status'] = $status;
        }

        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, PDO::PARAM_STR);
        }
        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    public function findPaginated(int $offset, int $limit, string $term = '', string $status = ''): array
    {
        $sql = "SELECT i.lpa_invoices_ID AS id,
                       i.lpa_inv_no AS invoice_number,
                       i.lpa_inv_slug AS slug,
                       COALESCE(i.lpa_inv_client_name, CONCAT(c.lpa_clients_firstname, ' ', c.lpa_clients_lastname)) AS client_name,
                       i.lpa_inv_date AS created_at,
                       i.lpa_inv_status AS status,
                       i.lpa_inv_amount AS total_amount
                FROM {$this->table} i
                JOIN lpa_clients c ON c.lpa_clients_ID = i.lpa_fk_clients_ID";

        $conditions = [];
        $params = [':offset' => $offset, ':limit' => $limit];

        if ($term !== '') {
            $conditions[] = "(i.lpa_inv_no LIKE :term1 OR i.lpa_inv_client_name LIKE :term2 OR CONCAT(c.lpa_clients_firstname, ' ', c.lpa_clients_lastname) LIKE :term3)";
            $params[':term1'] = "%{$term}%";
            $params[':term2'] = "%{$term}%";
            $params[':term3'] = "%{$term}%";
        }

        if ($status !== '') {
            $conditions[] = "i.lpa_inv_status = :status";
            $params[':status'] = $status;
        }

        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY i.lpa_invoices_ID DESC LIMIT :offset, :limit';

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['slug'] = $this->ensureSlug((int)$row['id'], $row['invoice_number']);
        }
        return $rows;
    }

    public function getInvoiceWithItems(int $invoiceId, ?int $userId = null): ?array
    {
        // 1) Invoice (optional user scoping)
        $q1 = /** @lang text */
            "SELECT i.lpa_invoices_ID      AS id,
               i.lpa_inv_no           AS invoice_number,
               i.lpa_inv_slug         AS slug,
               i.lpa_inv_date         AS created_at,
               i.lpa_inv_status       AS status,
               i.lpa_inv_amount       AS total_amount,
               i.lpa_inv_payment_method AS payment_method,
               i.lpa_inv_card_brand     AS card_brand,
               i.lpa_inv_card_last4     AS card_last4,
               i.lpa_inv_client_name  AS client_name,
               i.lpa_inv_client_address AS client_address,
               c.lpa_clients_firstname,
               c.lpa_clients_lastname,
               c.lpa_client_email AS client_email,
               c.lpa_client_phone AS client_phone
        FROM lpa_invoices i
        JOIN lpa_clients c ON c.lpa_clients_ID = i.lpa_fk_clients_ID
        WHERE i.lpa_invoices_ID = :invoice_id";

        $params = [':invoice_id' => $invoiceId];
        if ($userId !== null) {
            $q1     .= " AND c.lpa_clients_fk_user_id = :user_id";
            $params[':user_id'] = $userId;
        }
        $q1 .= " LIMIT 1";

        $stmt = $this->conn->prepare($q1);
        $stmt->execute($params);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$invoice) return null;
        $invoice['slug'] = $this->ensureSlug((int)$invoice['id'], $invoice['invoice_number']);

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

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->conn->prepare(/** @lang text */
            "SELECT * FROM {$this->table} WHERE lpa_inv_slug = :slug LIMIT 1"
        );
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getInvoiceWithItemsBySlug(string $slug, ?int $userId = null): ?array
    {
        $invoiceRow = $this->findBySlug($slug);
        if (!$invoiceRow) {
            return null;
        }
        $data = $this->getInvoiceWithItems((int)$invoiceRow['lpa_invoices_ID'], $userId);
        return $data;
    }
}
