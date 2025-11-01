<?php

require_once __DIR__ . '/BaseApiController.php';
require_once __DIR__ . '/../../services/ProductService.php';

class ProductApiController extends BaseApiController
{
    private ProductService $productService;

    public function __construct()
    {
        $this->productService = new ProductService();
    }

    public function index(): void
    {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        $result = $this->productService->listProducts($limit, $offset);
        $this->respond($result);
    }

    public function show(string $identifier): void
    {
        $result = $this->productService->getProduct($identifier);
        $this->respond($result);
    }
}
