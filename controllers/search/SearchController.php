<?php
require_once 'includes/config.php';
loadRepo('repositories/ProductRepository.php');

class SearchController
{
    public function products()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $query = trim($_GET['q'] ?? '');
        if (strlen($query) < 3) {
            echo json_encode(['html' => '']);
            return;
        }

        $conn = Database::getConnection();
        $stmt = $conn->prepare('SELECT lpa_stock_ID, lpa_stock_name, lpa_stock_slug FROM lpa_stock WHERE lpa_stock_name LIKE ? ORDER BY lpa_stock_name LIMIT 10');
        $stmt->execute(["%{$query}%"]);
        $products = $stmt->fetchAll();
        $repo = new ProductRepository();
        foreach ($products as &$product) {
            $product['lpa_stock_slug'] = $repo->ensureSlug((int)$product['lpa_stock_ID'], $product['lpa_stock_name'] ?? '');
        }
        unset($product);

        $accounts = [];
        $needle = strtolower($query);
        if (isset($_SESSION['user'])) {
            $options = [
                [
                    'keywords' => ['order', 'orders'],
                    'label'    => 'Show Orders',
                    'url'      => '/profile#orders',
                ],
                [
                    'keywords' => ['cancellation', 'cancel', 'cancellations'],
                    'label'    => 'My Cancellations',
                    'url'      => '/profile#cancellations',
                ],
                [
                    'keywords' => ['return', 'returns'],
                    'label'    => 'My Returns',
                    'url'      => '/profile#returns',
                ],
                [
                    'keywords' => ['profile', 'account'],
                    'label'    => 'My Profile',
                    'url'      => '/profile#profile',
                ],
                [
                    'keywords' => ['address', 'addresses'],
                    'label'    => 'Address Book',
                    'url'      => '/profile#address',
                ],
            ];

            foreach ($options as $opt) {
                foreach ($opt['keywords'] as $kw) {
                    if (str_contains($kw, $needle) || str_contains(strtolower($opt['label']), $needle)) {
                        $accounts[] = ['label' => $opt['label'], 'url' => $opt['url']];
                        break;
                    }
                }
            }
        }

        ob_start();
        include 'pages/templates/search_results.php';
        $html = ob_get_clean();

        header('Content-Type: application/json');
        echo json_encode(['html' => $html]);
    }
}
