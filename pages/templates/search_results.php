<?php if (!empty($accounts)): ?>
    <?php foreach ($accounts as $acc): ?>
        <a href="<?= $acc['url'] ?>" class="list-group-item list-group-item-action">
            <?= htmlspecialchars($acc['label']) ?>
        </a>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($products)): ?>
    <?php foreach ($products as $product): ?>
        <a href="/product?id=<?= $product['lpa_stock_ID'] ?>" class="list-group-item list-group-item-action">
            <?= htmlspecialchars($product['lpa_stock_name']) ?>
        </a>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (empty($products) && empty($accounts)): ?>
    <div class="list-group-item">No results found.</div>
<?php endif; ?>
