<?php
require_once 'includes/config.php';
$conn = Database::getConnection();
$query = $_GET['query'] ?? '';
$results = [];
if ($query !== '') {
    $stmt = $conn->prepare("SELECT lpa_stock_ID, lpa_stock_name, lpa_stock_price FROM lpa_stock WHERE lpa_stock_name LIKE ?");
    $stmt->execute(['%' . $query . '%']);
    $results = $stmt->fetchAll();
}
?>
<div class="container py-5">
    <h1 class="mb-4">Search Results for "<?= htmlspecialchars($query) ?>"</h1>
    <?php if (empty($results)): ?>
        <p>No products found.</p>
    <?php else: ?>
        <ul class="list-unstyled">
            <?php foreach ($results as $product): ?>
                <li class="mb-2">
                    <a href="/product?id=<?= $product['lpa_stock_ID'] ?>" class="text-decoration-none">
                        <?= htmlspecialchars($product['lpa_stock_name']) ?> - $<?= number_format($product['lpa_stock_price'], 2) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
