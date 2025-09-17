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
            'lpa_stock_name'     => $data['name'],
            'lpa_stock_slug'     => $this->generateUniqueSlug($data['name']),
            'lpa_stock_desc'     => $data['desc'] ?? null,
            'lpa_stock_features' => $data['features'] ?? null,
            'lpa_stock_onhand'   => $data['onhand'] ?? null,
            'lpa_stock_price'    => $data['price'] ?? 0,
            'lpa_stock_image'    => $data['image'] ?? null,
            'lpa_stock_status'   => $data['status'] ?? 'P',
            'lpa_stock_publish_at'=> $data['publish_at'] ?? null,
            'lpa_fk_category_ID' => $data['category_id'] ?? 0,
            'lpa_fk_type_ID'     => $data['type_id'] ?? 0,
            'lpa_invitem_inv_no' => $this->generateSku(),
        ]);
    }

    public function updateProduct($id, array $data): bool
    {
        return $this->update($id, [
            'lpa_stock_name'      => $data['name'],
            'lpa_stock_slug'      => $this->generateUniqueSlug($data['name'], (int)$id),
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

    public function updateStatus(int $id, string $status): bool
    {
        return $this->update($id, [
            'lpa_stock_status' => $status,
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

    public function countAll(string $search = '', string $status = ''): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} s";
        $conditions = [];
        $params = [];
        if ($search !== '') {
            $conditions[] = "(s.lpa_stock_name LIKE :term OR s.lpa_invitem_inv_no LIKE :term OR s.lpa_stock_price LIKE :term)";
            $params[':term'] = "%{$search}%";
        }
        if ($status !== '') {
            switch ($status) {
                case 'P':
                    $conditions[] = "(s.lpa_stock_status IN ('P','A') OR (s.lpa_stock_status='S' AND s.lpa_stock_publish_at <= NOW()))";
                    break;
                case 'U':
                    $conditions[] = "s.lpa_stock_status IN ('U','I')";
                    break;
                case 'S':
                    $conditions[] = "s.lpa_stock_status = 'S' AND (s.lpa_stock_publish_at IS NULL OR s.lpa_stock_publish_at > NOW())";
                    break;
                default:
                    $conditions[] = "s.lpa_stock_status = :status";
                    $params[':status'] = $status;
            }
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

    public function findPaginated(int $offset, int $limit, string $search = '', string $status = ''): array
    {
        $conditions = [];
        $params = [':offset' => $offset, ':limit' => $limit];
        if ($search !== '') {
            $conditions[] = "(s.lpa_stock_name LIKE :term OR s.lpa_invitem_inv_no LIKE :term OR s.lpa_stock_price LIKE :term)";
            $params[':term'] = "%{$search}%";
        }
        if ($status !== '') {
            switch ($status) {
                case 'P':
                    $conditions[] = "(s.lpa_stock_status IN ('P','A') OR (s.lpa_stock_status='S' AND s.lpa_stock_publish_at <= NOW()))";
                    break;
                case 'U':
                    $conditions[] = "s.lpa_stock_status IN ('U','I')";
                    break;
                case 'S':
                    $conditions[] = "s.lpa_stock_status = 'S' AND (s.lpa_stock_publish_at IS NULL OR s.lpa_stock_publish_at > NOW())";
                    break;
                default:
                    $conditions[] = "s.lpa_stock_status = :status";
                    $params[':status'] = $status;
            }
        }
        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
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

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT s.*, c.lpa_category_name, t.lpa_type_name
             FROM {$this->table} s
             JOIN lpa_category c ON s.lpa_fk_category_ID = c.lpa_category_ID
             JOIN lpa_type t ON s.lpa_fk_type_ID = t.lpa_type_ID
             WHERE s.lpa_stock_slug = :slug LIMIT 1"
        );
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function ensureSlug(int $id, string $name): string
    {
        $stmt = $this->conn->prepare("SELECT lpa_stock_slug FROM {$this->table} WHERE lpa_stock_ID = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $slug = $stmt->fetchColumn();
        if (!empty($slug)) {
            return (string)$slug;
        }

        $slug = $this->generateUniqueSlug($name);
        $update = $this->conn->prepare("UPDATE {$this->table} SET lpa_stock_slug = :slug WHERE lpa_stock_ID = :id");
        $update->execute([':slug' => $slug, ':id' => $id]);

        return $slug;
    }

    private function slugify(string $text): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text), '-'));
        return $slug !== '' ? $slug : uniqid('prod-');
    }

    private function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $slug = $this->slugify($name);
        $base = $slug;
        $i = 1;
        while ($this->slugExists($slug, $ignoreId)) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    private function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE lpa_stock_slug = :slug";
        if ($ignoreId) {
            $sql .= " AND lpa_stock_ID <> :id";
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':slug', $slug, PDO::PARAM_STR);
        if ($ignoreId) {
            $stmt->bindValue(':id', $ignoreId, PDO::PARAM_INT);
        }
        $stmt->execute();
        return (bool)$stmt->fetchColumn();
    }
}

