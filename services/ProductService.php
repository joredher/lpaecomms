<?php

require_once __DIR__ . '/../includes/config.php';
loadRepo('repositories/ProductRepository.php');

class ProductService
{
    private ProductRepository $productRepository;

    public function __construct(?ProductRepository $productRepository = null)
    {
        $this->productRepository = $productRepository ?? new ProductRepository();
    }

    public function getFilterOptions(): array
    {
        return [
            'categories' => $this->productRepository->getCategories(),
            'types' => $this->productRepository->getTypes(),
        ];
    }

    public function getProducts(array $queryParams, int $pageSize = 9): array
    {
        $page = isset($queryParams['page_num']) && is_numeric($queryParams['page_num'])
            ? (int)$queryParams['page_num']
            : 1;
        $page = max(1, $page);

        $filters = [
            'category' => $queryParams['category'] ?? [],
            'type' => $queryParams['type'] ?? [],
            'min_price' => $queryParams['min_price'] ?? null,
            'max_price' => $queryParams['max_price'] ?? null,
            'sort' => $queryParams['sort'] ?? '',
        ];

        $result = $this->productRepository->getFilteredProducts($filters, $page, $pageSize);

        return [
            'items' => $result['items'],
            'pagination' => $result['pagination'],
            'filters' => [
                'category' => $this->normalizeArrayFilter($filters['category']),
                'type' => $this->normalizeArrayFilter($filters['type'], false),
                'min_price' => $this->normalizeNumeric($filters['min_price']),
                'max_price' => $this->normalizeNumeric($filters['max_price']),
                'sort' => is_string($filters['sort']) ? $filters['sort'] : '',
            ],
        ];
    }

    private function normalizeArrayFilter($value, bool $stripAll = true): array
    {
        if (!is_array($value)) {
            $value = $value === null ? [] : [$value];
        }

        $normalized = array_values(array_filter(
            array_map('intval', $value),
            static fn($id) => $id > 0
        ));

        if ($stripAll) {
            $normalized = array_values(array_filter($normalized, static fn($id) => $id !== 4));
        }

        return $normalized;
    }

    private function normalizeNumeric($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float)$value : null;
    }
}
