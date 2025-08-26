<?php if (!empty($products)): ?>
    <?php foreach ($products as $product): ?>
        <a href="/product?id=<?= $product['lpa_stock_ID'] ?>" class="list-group-item list-group-item-action">
            <?= htmlspecialchars($product['lpa_stock_name']) ?>
        </a>
    <?php endforeach; ?>
<?php else: ?>
    <div class="list-group-item">No products found.</div>
<?php endif; ?>
