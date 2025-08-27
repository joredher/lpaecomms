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
            'lpa_stock_name'      => $data['name'],
            'lpa_stock_desc'      => $data['desc'] ?? null,
            'lpa_stock_features'  => $data['features'] ?? null,
            'lpa_stock_onhand'    => $data['onhand'] ?? null,
            'lpa_stock_price'     => $data['price'] ?? 0,
            'lpa_stock_image'     => $data['image'] ?? null,
            'lpa_stock_status'    => $data['status'] ?? 'A',
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
            'lpa_stock_status'    => $data['status'] ?? 'A',
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

    public function countAll(): int
    {
        $stmt = $this->conn->query("SELECT COUNT(*) FROM {$this->table}");
        return (int)$stmt->fetchColumn();
    }

    public function findPaginated(int $offset, int $limit): array
    {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} ORDER BY lpa_stock_ID DESC LIMIT :offset, :limit");
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getCategories(): array
    {
        $stmt = $this->conn->query(
            'SELECT lpa_category_ID, lpa_category_name FROM lpa_category ORDER BY lpa_category_name'
        );
        return $stmt->fetchAll();
    }

    public function getTypesByCategory(int $categoryId): array
    {
        $stmt = $this->conn->prepare(
            'SELECT t.lpa_type_ID, t.lpa_type_name
             FROM lpa_type t
             JOIN lpa_category_type ct ON t.lpa_type_ID = ct.lpa_type_fk_ID
             WHERE ct.lpa_category_fk_ID = :categoryId
             ORDER BY t.lpa_type_name'
        );
        $stmt->execute(['categoryId' => $categoryId]);
        return $stmt->fetchAll();
    }
}

