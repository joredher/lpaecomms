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
$status = $product['lpa_stock_status'] ?? '';
$isUnavailable = strtoupper((string)$status) === 'D';
$buyButtonClasses = 'btn-buy-now' . ($isUnavailable ? ' disabled' : '');
$cartButtonClasses = 'btn-add-to-cart' . ($isUnavailable ? ' disabled' : '');
$disabledAttributes = $isUnavailable ? 'disabled aria-disabled="true"' : '';

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

error_log('Product page loaded for product ID: ' . $productId);

?>

<div class="pd-details-section container py-5">
    <div class="pd-details-back mb-4">
        <a href="/products" class="pd-back-button d-flex align-items-center text-decoration-none">
            <img src="../assets/images/icons/back.svg" alt="Back" class="me-2">
            <span>Back</span>
        </a>
    </div>

    <?php
        $imagesRaw = (string)($product['lpa_stock_image'] ?? '');
        $imageParts = strpos($imagesRaw, ',') !== false ? array_map('trim', explode(',', $imagesRaw)) : [$imagesRaw];
        $gallery = array_values(array_filter(array_map('getProductImageUrl', $imageParts)));
        if (empty($gallery)) { $gallery = [getProductImageUrl($product['lpa_stock_image'] ?? '')]; }
        $gallery = array_filter($gallery);
        $mainImageUrl = htmlspecialchars($gallery[0] ?? '');
    ?>
    <div class="details-product">
        <div class="pd-gallery">
            <div class="pd-image-zoom">
                <img id="pd-main-image" class="image-1-icon" src="<?= $mainImageUrl ?>" alt="<?= htmlspecialchars($product['lpa_stock_name']) ?>">
            </div>
            <div class="pd-thumbs mt-3">
                <?php foreach ($gallery as $gi => $src): ?>
                    <img class="pd-thumb <?= $gi === 0 ? 'active' : '' ?>" src="<?= htmlspecialchars($src) ?>" alt="thumb-<?= $gi ?>" data-full="<?= htmlspecialchars($src) ?>">
                <?php endforeach; ?>
            </div>
        </div>

        <div class="pd-main">
            <div class="product-name">
                <?= htmlspecialchars($product['lpa_stock_name']) ?>
            </div>
            <div class="au">
                <span class="price fw-bolder" data-price-aud="<?= number_format((float)$product['lpa_stock_price'], 2, '.', '') ?>">$<?= number_format((float)$product['lpa_stock_price'], 2) ?> AUD</span>
            </div>
            <div class="what-you-need-container">
                <p class="what-you-need">What you need to know about this product:</p>
                <p class="optimize-your-machines-perfor">Description: <?= $product['lpa_stock_desc'] ?></p>
            </div>
            <div class="product-actions">
                <button type="button"
                        class="<?= htmlspecialchars($buyButtonClasses, ENT_QUOTES) ?>"
                        <?= $disabledAttributes ?>
                        onclick="buyNow(event, <?= (int)$product['lpa_stock_ID'] ?>)"
                        title="<?= htmlspecialchars($isUnavailable ? 'Product unavailable for purchase' : 'Buy now', ENT_QUOTES) ?>">
                    Buy now
                </button>
                <button class="<?= htmlspecialchars($cartButtonClasses, ENT_QUOTES) ?>"
                        data-product-id="<?= htmlspecialchars($product['lpa_stock_ID']); ?>"
                        onclick="addToCartBtn(event)"
                        <?= $disabledAttributes ?>
                        title="<?= htmlspecialchars($isUnavailable ? 'Product unavailable for purchase' : 'Add to cart', ENT_QUOTES) ?>">
                    Add to cart
                </button>
            </div>
            <?php if ($isUnavailable): ?>
                <p class="product-unavailable-note">This product is currently unavailable for purchase.</p>
            <?php endif; ?>
        </div>

        <details class="pd-specs" open>
            <summary class="d-lg-none">Specs</summary>
            <h6 class="pd-specs-title d-none d-lg-block">Specifications</h6>
            <?php if (!empty($features)): ?>
                <ul class="product-features">
                    <?php foreach ($features as $feature): ?>
                        <li><?= htmlspecialchars((string)$feature) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-muted">No additional specifications.</p>
            <?php endif; ?>
        </details>
    </div>

    <div class="pd-related-section mt-5">
        <h4 class="pd-related-title mb-5">You may also like</h4>
        <form method="GET" id="filter-form" action="index.php">
            <input type="hidden" name="page" value="product">
            <div class="row gy-4 mb-5">
                <?php foreach ($related as $item): ?>
                    <?php
                        $relatedStatus = strtoupper((string)($item['lpa_stock_status'] ?? ''));
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
                                    <div class="price fw-bolder" data-price-aud="<?= number_format((float)$item['lpa_stock_price'], 2, '.', '') ?>">$<?= number_format((float)$item['lpa_stock_price'], 2) ?> AUD</div>
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



