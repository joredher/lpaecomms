<?php
require_once 'includes/config.php';

class SearchController
{
    public function products()
    {
        $query = trim($_GET['q'] ?? '');
        if (strlen($query) < 3) {
            echo json_encode(['html' => '']);
            return;
        }

        $conn = Database::getConnection();
        $stmt = $conn->prepare('SELECT lpa_stock_ID, lpa_stock_name FROM lpa_stock WHERE lpa_stock_name LIKE ? ORDER BY lpa_stock_name LIMIT 10');
        $stmt->execute(["%{$query}%"]);
        $products = $stmt->fetchAll();

        ob_start();
        include 'pages/templates/search_results.php';
        $html = ob_get_clean();

        header('Content-Type: application/json');
        echo json_encode(['html' => $html]);
    }
}
