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

        $accounts = [];
        $needle = strtolower($query);
        foreach ($options as $opt) {
            foreach ($opt['keywords'] as $kw) {
                if (str_contains($kw, $needle) || str_contains(strtolower($opt['label']), $needle)) {
                    $accounts[] = ['label' => $opt['label'], 'url' => $opt['url']];
                    break;
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
