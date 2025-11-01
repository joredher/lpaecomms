<?php

require_once __DIR__ . '/../bootstrap.php';

loadRepo('repositories/ProductRepository.php');

class ProductService
{
    private ProductRepository $productRepo;

    public function __construct()
    {
        $this->productRepo = new ProductRepository();
    }

    /**
     * @return array{success:bool,status:int,data?:array,message?:string}
     */
    public function listProducts(int $limit = 50, int $offset = 0): array
    {
        $items = $this->productRepo->findPaginated($offset, $limit, '', 'P');
        $normalized = array_map(fn(array $item) => $this->normalizeProduct($item), $items);

        return [
            'success' => true,
            'status' => 200,
            'data' => [
                'items' => $normalized,
                'limit' => $limit,
                'offset' => $offset,
            ],
        ];
    }

    /**
     * @return array{success:bool,status:int,data?:array,message?:string}
     */
    public function getProduct(string $identifier): array
    {
        $product = is_numeric($identifier)
            ? $this->productRepo->findById((int)$identifier)
            : $this->productRepo->findBySlug($identifier);

        if (!$product) {
            return [
                'success' => false,
                'status' => 404,
                'message' => 'Product not found.',
            ];
        }

        return [
            'success' => true,
            'status' => 200,
            'data' => [
                'product' => $this->normalizeProduct($product),
            ],
        ];
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function normalizeProduct(array $row): array
    {
        return [
            'id' => (int)($row['lpa_stock_ID'] ?? $row['lpa_stock_id'] ?? 0),
            'name' => $row['lpa_stock_name'] ?? '',
            'slug' => $row['lpa_stock_slug'] ?? null,
            'description' => $row['lpa_stock_desc'] ?? null,
            'features' => $row['lpa_stock_features'] ?? null,
            'price' => (float)($row['lpa_stock_price'] ?? 0),
            'image' => getProductImageUrl($row['lpa_stock_image'] ?? null),
            'status' => $row['lpa_stock_status'] ?? null,
            'available' => isset($row['available']) ? (int)$row['available'] : null,
            'on_hand' => isset($row['lpa_stock_onhand']) ? (int)$row['lpa_stock_onhand'] : null,
        ];
    }
}
