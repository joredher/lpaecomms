<?php

use repositories\BaseRepository;

loadRepo('repositories/BaseRepository.php');

/**
 * Repository for managing products stored in the lpa_stock table.
 */
class ProductRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct('lpa_stock');
    }

    /**
     * Create a new product. The SKU is generated automatically.
     */
    public function createProduct(array $data): bool
    {
        // Remove any external SKU input and generate internally
        unset($data['sku'], $data['lpa_invitem_inv_no']);
        $data['lpa_invitem_inv_no'] = $this->generateSku();

        return $this->create($data);
    }

    /**
     * Update product information without modifying the SKU.
     */
    public function updateProduct(int $id, array $data): bool
    {
        // Ensure SKU remains unchanged
        unset($data['sku'], $data['lpa_invitem_inv_no']);

        return $this->update($id, $data);
    }

    /**
     * Generate the next SKU in the sequence.
     *
     * Fetches the latest lpa_invitem_inv_no and increments it.
     */
    private function generateSku(): string
    {
        $stmt = $this->conn->query(
            /** @lang text */
            "SELECT lpa_invitem_inv_no FROM {$this->table} ORDER BY CAST(SUBSTRING(lpa_invitem_inv_no, 5) AS UNSIGNED) DESC LIMIT 1"
        );
        $lastSku = $stmt->fetchColumn();

        if (!$lastSku) {
            return 'INV-001';
        }

        $number = (int) substr($lastSku, 4) + 1;
        return 'INV-' . str_pad((string) $number, 3, '0', STR_PAD_LEFT);
    }
}
