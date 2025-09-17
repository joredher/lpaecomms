<?php
require_once 'includes/config.php';
loadRepo('repositories/ProductRepository.php');
$conn = Database::getConnection();
$productRepo = new ProductRepository();

$productSlug = $productSlug ?? ($_GET['slug'] ?? null);
$productId   = $productId   ?? ($_GET['id'] ?? null);
if (!$productSlug && !$productId) {
    http_response_code(404);
    include 'pages/error/404.php';
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
    include 'pages/error/404.php';
    return;
}

$productId = $product['lpa_stock_ID'];
$product['lpa_stock_slug'] = $productRepo->ensureSlug((int)$productId, $product['lpa_stock_name'] ?? '');
$isUnavailable = ($product['lpa_stock_status'] ?? '') === 'D';

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
foreach ($related as &$item) {
    $item['lpa_stock_slug'] = $productRepo->ensureSlug((int)$item['lpa_stock_ID'], $item['lpa_stock_name'] ?? '');
}
unset($item);

$features = array_filter(
    array_map('trim', explode('.', (string)($product['lpa_stock_features'] ?? '')))
);
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
                <button class="btn-buy-now"
                        <?= $isUnavailable ? 'disabled aria-disabled="true"' : '' ?>
                        title="<?= htmlspecialchars($isUnavailable ? 'Product unavailable for purchase' : 'Buy now', ENT_QUOTES) ?>">
                    Buy now
                </button>
                <button class="btn-add-to-cart"
                        data-product-id="<?= htmlspecialchars($product['lpa_stock_ID']); ?>"
                        onclick="addToCartBtn(event)"
                        <?= $isUnavailable ? 'disabled aria-disabled="true"' : '' ?>
                        title="<?= htmlspecialchars($isUnavailable ? 'Product unavailable for purchase' : 'Add to cart', ENT_QUOTES) ?>">
                    Add to cart
                </button>
            </div>
            <?php if ($isUnavailable): ?>
                <p class="product-unavailable-note">This product is currently unavailable for purchase.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="pd-related-section mt-5">
        <h4 class="pd-related-title mb-5">You may also like</h4>
        <form method="GET" id="filter-form" action="index.php">
            <input type="hidden" name="page" value="product">
            <div class="row gy-4 mb-5">
                <?php foreach ($related as $item): ?>
                    <?php
                        $relatedStatus = $item['lpa_stock_status'] ?? '';
                        $relatedUnavailable = $relatedStatus === 'D';
                        $relatedCardClasses = 'product-card clickable-card clickable-card-detail ripple-container';
                        if ($relatedUnavailable) {
                            $relatedCardClasses .= ' disabled-product';
                        }
                        $relatedButtonClasses = 'btn btn-sm btn-outline-primary add-to-cart-btn';
                        if ($relatedUnavailable) {
                            $relatedButtonClasses .= ' disabled';
                        }
                        $relatedButtonTitle = $relatedUnavailable ? 'Product unavailable for purchase' : 'Add to cart';
                    ?>
                    <div class="col-md-4">
                        <div class="<?= htmlspecialchars($relatedCardClasses, ENT_QUOTES) ?>" data-slug="<?= htmlspecialchars($item['lpa_stock_slug'] ?? $item['lpa_stock_ID']) ?>" data-id="<?= htmlspecialchars($item['lpa_stock_ID']) ?>" data-status="<?= htmlspecialchars($relatedStatus) ?>">
                            <img src="<?= htmlspecialchars(getProductImageUrl($item['lpa_stock_image'] ?? '') ?? '') ?>" alt="<?= htmlspecialchars($item['lpa_stock_name'] ?? '') ?>">

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
                                            class="<?= htmlspecialchars($relatedButtonClasses, ENT_QUOTES) ?>"
                                            <?= $relatedUnavailable ? 'disabled aria-disabled="true"' : '' ?>
                                            title="<?= htmlspecialchars($relatedButtonTitle, ENT_QUOTES) ?>">
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



