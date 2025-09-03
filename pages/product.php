<?php
require_once 'includes/config.php';
$conn = Database::getConnection();

$productSlug = $productSlug ?? ($_GET['slug'] ?? null);
$productId   = $productId   ?? ($_GET['id'] ?? null);
if (!$productSlug && !$productId) {
    http_response_code(404);
    include 'pages/client/404.php';
    return;
}

$where = $productSlug ? 's.lpa_stock_slug = ?' : 's.lpa_stock_ID = ?';
$query = /** @lang text */
    "SELECT s.*, c.lpa_category_name, t.lpa_type_name
          FROM lpa_stock s
          JOIN lpa_category c ON s.lpa_fk_category_ID = c.lpa_category_ID
          JOIN lpa_type t ON s.lpa_fk_type_ID = t.lpa_type_ID
          WHERE {$where}";
$stmt = $conn->prepare($query);
$stmt->execute([$productSlug ?: $productId]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    include 'pages/client/404.php';
    return;
}

$productId = $product['lpa_stock_ID'];

$relatedStmt = $conn->prepare(
    /** @lang text */ "SELECT
        s.*,
        c.lpa_category_name,
        t.lpa_type_name
     FROM lpa_stock s
     JOIN lpa_category c ON s.lpa_fk_category_ID = c.lpa_category_ID
     JOIN lpa_type t ON s.lpa_fk_type_ID = t.lpa_type_ID
     WHERE lpa_fk_category_ID = ? AND lpa_stock_ID != ?
     ORDER BY RAND() LIMIT 3"
);
$relatedStmt->execute([$product['lpa_fk_category_ID'], $productId]);
$related = $relatedStmt->fetchAll();

$features = array_filter(
    array_map('trim', explode('.', (string)($product['lpa_stock_features'] ?? '')))
);

function getProductSlug(array $product): string {
    if (!empty($product['lpa_stock_slug'])) {
        return (string)$product['lpa_stock_slug'];
    }

    $name = $product['lpa_stock_name'] ?? '';
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));

    return $slug !== '' ? $slug : (string)($product['lpa_stock_ID'] ?? '');
}
?>

<div class="pd-details-section container py-5">
    <div class="pd-details-back mb-4">
        <a href="/products" class="pd-back-button d-flex align-items-center text-decoration-none">
            <img src="../assets/images/icons/back.svg" alt="Back" class="me-2">
            <span>Back</span>
        </a>
    </div>

    <div class="details-product row g-0 row-cols-3 m-auto">
        <div>
            <div class="details-product-child"></div>
            <img class="image-1-icon" alt="Product Image" src="<?= htmlspecialchars(getProductImageUrl($product['lpa_stock_image'] ?? '')) ?>">
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
                                <li><?= htmlspecialchars((string)$feature) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <div class="product-actions">
                <button class="btn-buy-now">Buy now</button>
                <button class="btn-add-to-cart"
                        data-product-id="<?= htmlspecialchars($product['lpa_stock_ID']); ?>"
                        onclick="addToCartBtn(event)"
                >Add to cart</button>
            </div>
        </div>
    </div>

    <div class="pd-related-section mt-5">
        <h4 class="pd-related-title mb-5">You may also like</h4>
        <form method="GET" id="filter-form" action="index.php">
            <input type="hidden" name="page" value="product">
            <div class="row gy-4 mb-5">
                <?php foreach ($related as $item): ?>
                    <div class="col-md-4">
                        <div class="product-card clickable-card clickable-card-detail ripple-container" data-slug="<?= htmlspecialchars(getProductSlug($item)) ?>" data-id="<?= htmlspecialchars($item['lpa_stock_ID']) ?>">
                            <img src="<?= htmlspecialchars((string)getProductImageUrl($item['lpa_stock_image'] ?? '') ?? '') ?>" alt="<?= htmlspecialchars($item['lpa_stock_name'] ?? '') ?>">

                            <div class="product-card-description">
                                <h3 class="text-truncate"><?= htmlspecialchars($item['lpa_stock_name']) ?></h3>
                                <div class="type-category-text">
                                    <p class="text-truncate">
                                        Category: <strong><?= $item['lpa_category_name'] ?></strong><br>
                                    </p>
                                    <p>*</p>
                                    <p class="text-truncate">
                                        Type: <strong><?= $item['lpa_type_name'] ?></strong>
                                    </p>
                                </div>
                                <div class="product-card-add-cart">
                                    <div class="price fw-bolder">$<?= number_format($item['lpa_stock_price'], 2) ?> AUD</div>
                                    <button data-product-id="<?= htmlspecialchars($item['lpa_stock_ID']); ?>"
                                            onclick="addToCartBtn(event)"
                                            class="btn btn-sm btn-outline-primary add-to-cart-btn">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                        Add
                                    </button>
<!--                                    <a class="btn btn-sm btn-outline-primary">-->
<!--                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>-->
<!--                                        Add-->
<!--                                    </a>-->
                                </div>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>

        </form>
    </div>
</div>
<script>
const cards = document.querySelectorAll('.clickable-card-detail');

cards.forEach(card => {
    card.addEventListener('click', (e) => {
      if (e.target.closest("button")) return;
      const slug = card.getAttribute("data-slug");
      if (slug) {
          window.location.href = `/product?slug=${slug}`;
      } else {
          const id = card.getAttribute("data-id");
          if (id) window.location.href = `/product?id=${id}`;
      }
      });
  });
  </script>



