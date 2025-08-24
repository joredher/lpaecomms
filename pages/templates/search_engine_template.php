<?php /** @var array $products */ ?>
<div class="container mt-4">
    <?php if (!empty($products)): ?>
        <ul class="list-group">
            <?php foreach ($products as $product): ?>
                <li class="list-group-item">
                    <?= htmlspecialchars($product['lpa_stock_name']) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No products found.</p>
    <?php endif; ?>
</div>
