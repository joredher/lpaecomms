<?php
require_once 'includes/config.php';

class SearchEngineController
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getConnection();
    }

    public function products(bool $return = false)
    {
        $query = trim($_GET['query'] ?? '');

        $stmt = $this->conn->prepare("SELECT * FROM lpa_stock WHERE lpa_stock_name LIKE :search");
        $stmt->execute([':search' => "%$query%"]);
        $products = $stmt->fetchAll();

        if ($return) {
            return $products;
        }

        include 'pages/templates/search_engine_template.php';
    }
}
