<?php

use repositories\BaseRepository;

loadRepo('repositories/BaseRepository.php');

class ProductRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct('lpa_stock');
    }

    public function createProduct(array $data): bool
    {
        return $this->create([
            'lpa_stock_ID'       => $this->generateStockId(),
            'lpa_stock_name'      => $data['name'],
            'lpa_stock_desc'      => $data['desc'] ?? null,
            'lpa_stock_features'  => $data['features'] ?? null,
            'lpa_stock_onhand'    => $data['onhand'] ?? null,
            'lpa_stock_price'     => $data['price'] ?? 0,
            'lpa_stock_image'     => $data['image'] ?? null,
            'lpa_stock_status'    => $data['status'] ?? 'P',
            'lpa_stock_publish_at'=> $data['publish_at'] ?? null,
            'lpa_fk_category_ID'  => $data['category_id'] ?? 0,
            'lpa_fk_type_ID'      => $data['type_id'] ?? 0,
            'lpa_invitem_inv_no'  => $this->generateSku(),
        ]);
    }

    public function updateProduct($id, array $data): bool
    {
        return $this->update($id, [
            'lpa_stock_name'      => $data['name'],
            'lpa_stock_desc'      => $data['desc'] ?? null,
            'lpa_stock_features'  => $data['features'] ?? null,
            'lpa_stock_onhand'    => $data['onhand'] ?? null,
            'lpa_stock_price'     => $data['price'] ?? 0,
            'lpa_stock_image'     => $data['image'] ?? null,
            'lpa_stock_status'    => $data['status'] ?? 'P',
            'lpa_stock_publish_at'=> $data['publish_at'] ?? null,
            'lpa_fk_category_ID'  => $data['category_id'] ?? 0,
            'lpa_fk_type_ID'      => $data['type_id'] ?? 0,
        ]);
    }

    private function generateSku(): string
    {
        do {
            $sku = 'SKU-' . strtoupper(bin2hex(random_bytes(4)));
        } while ($this->findWhere('lpa_invitem_inv_no', $sku));

        return $sku;
    }

    private function generateStockId(): int
    {
        $stmt = $this->conn->query("SELECT COALESCE(MAX(lpa_stock_ID), 0) + 1 FROM {$this->table}");
        return (int)$stmt->fetchColumn();
    }

    public function countAll(string $search = ''): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        $params = [];
        if ($search !== '') {
            $sql .= " WHERE lpa_stock_name LIKE :term OR lpa_invitem_inv_no LIKE :term OR lpa_stock_price LIKE :term";
            $params[':term'] = "%{$search}%";
        }
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, PDO::PARAM_STR);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function findPaginated(int $offset, int $limit, string $search = ''): array
    {
        $where = '';
        $params = [':offset' => $offset, ':limit' => $limit];
        if ($search !== '') {
            $where = "WHERE s.lpa_stock_name LIKE :term OR s.lpa_invitem_inv_no LIKE :term OR s.lpa_stock_price LIKE :term";
            $params[':term'] = "%{$search}%";
        }
        $sql = "SELECT s.*, s.lpa_stock_onhand - COALESCE(p.purchased, 0) AS available
                FROM {$this->table} s
                LEFT JOIN (
                    SELECT ii.lpa_fk_stock_ID, SUM(ii.lpa_invitem_qty) AS purchased
                    FROM lpa_invoice_items ii
                    JOIN lpa_invoices i ON i.lpa_invoices_ID = ii.lpa_fk_invoices_ID
                    AND i.lpa_inv_status = 'A'
                    GROUP BY ii.lpa_fk_stock_ID
                ) p ON p.lpa_fk_stock_ID = s.lpa_stock_ID
                {$where}
                ORDER BY s.lpa_stock_ID DESC
                LIMIT :offset, :limit";
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            if ($row['lpa_stock_status'] === 'S' && !empty($row['lpa_stock_publish_at']) && strtotime($row['lpa_stock_publish_at']) <= time()) {
                $row['lpa_stock_status'] = 'P';
            }
        }
        return $rows;
    }

    public function getCategories(): array
    {
        $stmt = $this->conn->query(
            'SELECT lpa_category_ID, lpa_category_name FROM lpa_category ORDER BY lpa_category_name'
        );
        return $stmt->fetchAll();
    }

    public function getTypes(): array
    {
        $stmt = $this->conn->query(
            'SELECT lpa_type_ID, lpa_type_name FROM lpa_type ORDER BY lpa_type_name'
        );
        return $stmt->fetchAll();
    }
}

