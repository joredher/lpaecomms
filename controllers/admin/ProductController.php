<?php
require_once 'includes/config.php';
loadRepo('repositories/ProductRepository.php');

class ProductController
{
    public function types(): void
    {
        header('Content-Type: application/json');
        $repo = new ProductRepository();
        $types = $repo->getTypes();
        $result = array_map(fn($t) => [
            'id' => $t['lpa_type_ID'],
            'name' => $t['lpa_type_name']
        ], $types);
        echo json_encode($result);
    }
}
