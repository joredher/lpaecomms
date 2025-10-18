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

    public function getFilteredProducts(array $filters, int $page, int $perPage): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $conditions = ["(s.lpa_stock_status IN ('P','A','D') OR (s.lpa_stock_status = 'S' AND s.lpa_stock_publish_at <= NOW()))"];
        $params = [];

        $typeFilter = $filters['type'] ?? [];
        if (!is_array($typeFilter)) {
            $typeFilter = [$typeFilter];
        }
        $typeFilter = array_values(array_filter(array_unique(array_map('intval', $typeFilter)), fn($id) => $id > 0 && $id !== 4));

        $categoryFilter = $filters['category'] ?? [];
        if (!is_array($categoryFilter)) {
            $categoryFilter = [$categoryFilter];
        }
        $categoryFilter = array_values(array_filter(array_unique(array_map('intval', $categoryFilter)), fn($id) => $id > 0));

        if (!empty($typeFilter)) {
            $placeholders = [];
            foreach ($typeFilter as $idx => $typeId) {
                $placeholder = ":type{$idx}";
                $placeholders[] = $placeholder;
                $params[$placeholder] = ['value' => $typeId, 'type' => PDO::PARAM_INT];
            }
            $conditions[] = 's.lpa_fk_type_ID IN (' . implode(',', $placeholders) . ')';
        } elseif (!empty($categoryFilter)) {
            $placeholders = [];
            foreach ($categoryFilter as $idx => $categoryId) {
                $placeholder = ":category{$idx}";
                $placeholders[] = $placeholder;
                $params[$placeholder] = ['value' => $categoryId, 'type' => PDO::PARAM_INT];
            }
            $conditions[] = 's.lpa_fk_category_ID IN (' . implode(',', $placeholders) . ')';
        }

        if (isset($filters['min_price']) && $filters['min_price'] !== '' && is_numeric($filters['min_price'])) {
            $params[':min_price'] = ['value' => (float)$filters['min_price'], 'type' => PDO::PARAM_STR];
            $conditions[] = 's.lpa_stock_price >= :min_price';
        }

        if (isset($filters['max_price']) && $filters['max_price'] !== '' && is_numeric($filters['max_price'])) {
            $params[':max_price'] = ['value' => (float)$filters['max_price'], 'type' => PDO::PARAM_STR];
            $conditions[] = 's.lpa_stock_price <= :max_price';
        }

        $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $orderBy = match ($filters['sort'] ?? '') {
            'price_low_high' => 'ORDER BY s.lpa_stock_price ASC',
            'price_high_low' => 'ORDER BY s.lpa_stock_price DESC',
            default => 'ORDER BY s.lpa_stock_ID DESC',
        };

        $baseFrom = "FROM {$this->table} s " .
            'JOIN lpa_category c ON s.lpa_fk_category_ID = c.lpa_category_ID ' .
            'JOIN lpa_type t ON s.lpa_fk_type_ID = t.lpa_type_ID';

        $countSql = "SELECT COUNT(*) {$baseFrom} {$whereClause}";
        $countStmt = $this->conn->prepare($countSql);
        $this->bindParams($countStmt, $params);
        $countStmt->execute();
        $totalItems = (int)$countStmt->fetchColumn();

        $dataSql = "SELECT s.*, c.lpa_category_name, t.lpa_type_name {$baseFrom} {$whereClause} {$orderBy} LIMIT :offset, :limit";
        $dataStmt = $this->conn->prepare($dataSql);
        foreach ($params as $placeholder => $info) {
            $dataStmt->bindValue($placeholder, $info['value'], $info['type']);
        }
        $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $dataStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $dataStmt->execute();

        $items = $dataStmt->fetchAll();
        foreach ($items as &$item) {
            if ($item['lpa_stock_status'] === 'S' && !empty($item['lpa_stock_publish_at']) && strtotime($item['lpa_stock_publish_at']) <= time()) {
                $item['lpa_stock_status'] = 'P';
            }
            $item['lpa_stock_slug'] = $this->ensureSlug((int)$item['lpa_stock_ID'], $item['lpa_stock_name'] ?? '');
        }
        unset($item);

        $totalPages = $perPage > 0 ? (int)ceil($totalItems / $perPage) : 0;

        return [
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total_items' => $totalItems,
                'total_pages' => $totalPages,
            ],
        ];
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

    private function bindParams(\PDOStatement $stmt, array $params): void
    {
        foreach ($params as $placeholder => $info) {
            $stmt->bindValue($placeholder, $info['value'], $info['type']);
        }
    }
}

