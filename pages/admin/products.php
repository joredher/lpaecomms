<?php
require_once __DIR__ . '/../../includes/config.php';
$conn = Database::getConnection();

// Fetch products for display
$stmt = $conn->query(
    /** @lang text */
    "SELECT lpa_stock_ID, lpa_stock_name, lpa_stock_price, lpa_invitem_inv_no FROM lpa_stock ORDER BY lpa_stock_ID DESC"
);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container">
    <h1 class="mb-4">Products</h1>

    <form id="product-form" class="mb-5">
        <input type="hidden" name="id" id="product_id">
        <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="mb-3">
            <label for="price" class="form-label">Price</label>
            <input type="number" step="0.01" class="form-control" id="price" name="price" required>
        </div>
        <button type="submit" class="btn btn-primary">Save Product</button>
    </form>

    <table class="table table-striped" id="products-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>SKU</th>
                <th>Name</th>
                <th>Price</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($products as $product): ?>
            <tr>
                <td><?= htmlspecialchars($product['lpa_stock_ID']) ?></td>
                <td><?= htmlspecialchars($product['lpa_invitem_inv_no']) ?></td>
                <td><?= htmlspecialchars($product['lpa_stock_name']) ?></td>
                <td>$<?= number_format($product['lpa_stock_price'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script src="/assets/js/admin_products.js"></script>
