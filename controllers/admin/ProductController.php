<?php
require_once 'includes/config.php';
loadRepo('repositories/ProductRepository.php');

class ProductController
{
    public function types(): void
    {
        header('Content-Type: application/json');
        $categoryId = (int)($_GET['category_id'] ?? 0);
        if (!$categoryId) {
            echo json_encode([]);
            return;
        }

        $repo = new ProductRepository();
        $types = $repo->getTypesByCategory($categoryId);
        $result = array_map(fn($t) => [
            'id' => $t['lpa_type_ID'],
            'name' => $t['lpa_type_name']
        ], $types);
        echo json_encode($result);
    }
}
