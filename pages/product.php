<?php
require_once 'includes/config.php';
$conn = Database::getConnection();

$productId = $_GET['id'] ?? null;
if (!$productId || !is_numeric($productId)) {
    header('Location: index.php?page=categories');
    exit;
}

$query = /** @lang text */
    "SELECT s.*, c.lpa_category_name, t.lpa_type_name 
          FROM lpa_stock s
          JOIN lpa_category c ON s.lpa_fk_category_ID = c.lpa_category_ID
          JOIN lpa_type t ON s.lpa_fk_type_ID = t.lpa_type_ID
          WHERE s.lpa_stock_ID = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    echo "<p>Product not found.</p>";
    exit;
}

$relatedStmt = $conn->prepare(
    /** @lang text */ "SELECT * FROM lpa_stock 
     WHERE lpa_fk_category_ID = ? AND lpa_stock_ID != ? 
     ORDER BY RAND() LIMIT 3"
);
$relatedStmt->execute([$product['lpa_fk_category_ID'], $productId]);
$related = $relatedStmt->fetchAll();

$features = explode('.', $product['lpa_stock_features']) ?? [];
?>

<div class="pd-details-section container py-5">
    <div class="pd-details-back mb-4">
        <a href="index.php?page=categories" class="pd-back-button d-flex align-items-center text-decoration-none">
            <img src="../assets/images/icons/back.svg" alt="Back" class="me-2">
            <span>Back</span>
        </a>
    </div>

    <div class="details-product row g-0 row-cols-3 m-auto">
        <div>
            <div class="details-product-child"></div>
            <img class="image-1-icon" alt="Product Image" src="assets/images/test-images/<?= htmlspecialchars($product['lpa_stock_image']) ?>">
        </div>
        <div>
            <div class="product-name">
                <?= htmlspecialchars($product['lpa_stock_name']) ?>
            </div>
            <div class="au">
                $<?= number_format($product['lpa_stock_price'], 2) ?> AU
            </div>
            <div class="what-you-need-container">
                <div class="what-you-need-container1">
                    <p class="what-you-need">What you need to know about this product:</p>
                    <p class="optimize-your-machines-perfor">
                        Description: <?= $product['lpa_stock_desc'] ?>
                    </p>

                    <?php if (!empty($features)): ?>
                        <ul class="product-features">
                            <?php foreach ($features as $feature): ?>
                                <li><?= htmlspecialchars($feature) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <div class="product-actions">
                <button class="btn-buy-now">Buy now</button>
                <button class="btn-add-to-cart">Add to cart</button>
            </div>
        </div>
    </div>

<!--    <div class="pd-related-section mt-5">-->
<!--        <div class="pd-related-bg"></div>-->
<!--        <h4 class="pd-related-title mb-4">You may also like</h4>-->
<!--        <div class="row gy-4">-->
<!--            --><?php //foreach ($related as $item): ?>
<!--                <div class="col-md-4">-->
<!--                    <div class="pd-related-card h-100 shadow-sm rounded p-3 bg-white">-->
<!--                        <img class="pd-related-img mb-3 w-100 rounded" src="assets/images/test-images/--><?php //= htmlspecialchars($item['lpa_stock_image']) ?><!--" alt="--><?php //= htmlspecialchars($item['lpa_stock_name']) ?><!--">-->
<!--                        <div class="pd-related-info">-->
<!--                            <h5 class="pd-related-name mb-2 fw-semibold text-truncate">--><?php //= htmlspecialchars($item['lpa_stock_name']) ?><!--</h5>-->
<!--                            <div class="pd-related-meta mb-2">-->
<!--                                <div class="pd-meta-item">-->
<!--                                    <span class="pd-meta-label">Type:</span>-->
<!--                                    <span class="pd-meta-value ms-1 text-muted">--><?php //= htmlspecialchars($item['lpa_type_name']) ?><!--</span>-->
<!--                                </div>-->
<!--                                <div class="pd-meta-item">-->
<!--                                    <span class="pd-meta-label">Category:</span>-->
<!--                                    <span class="pd-meta-value ms-1 text-muted">--><?php //= htmlspecialchars($item['lpa_category_name']) ?><!--</span>-->
<!--                                </div>-->
<!--                            </div>-->
<!--                            <div class="pd-related-price fw-bold">$--><?php //= number_format($item['lpa_stock_price'], 2) ?><!-- AUD</div>-->
<!--                        </div>-->
<!--                    </div>-->
<!--                </div>-->
<!--            --><?php //endforeach; ?>
<!--        </div>-->
<!--    </div>-->
</div>



