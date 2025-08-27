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
            'lpa_invitem_inv_no'  => $data['sku'] ?? '',
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
            'lpa_invitem_inv_no'  => $data['sku'] ?? '',
        ]);
    }
}
