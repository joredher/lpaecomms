<?php
require_once 'includes/config.php';
require_once 'includes/pagination.php';
require_once 'services/ProductService.php';

$productService = new ProductService();
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

$filterOptions = $productService->getFilterOptions();
$listing = $productService->getProducts($_GET, 9);

$categories = $filterOptions['categories'];
$types = $filterOptions['types'];
$products = $listing['items'];
$pagination = $listing['pagination'];
$selectedFilters = $listing['filters'];
$pageNum = $pagination['page'];
$totalPages = $pagination['total_pages'];

function renderProducts(array $products): string {
    ob_start();
    foreach ($products as $product):
        $slug = $product['lpa_stock_slug'] ?? (string)$product['lpa_stock_ID'];
        $status = strtoupper((string)($product['lpa_stock_status'] ?? ''));
        $isUnavailable = $status === 'D';
        $cardClasses = 'product-card clickable-card ripple-container';
        if ($isUnavailable) {
            $cardClasses .= ' disabled-product';
        }
        $buttonClasses = 'btn btn-sm btn-outline-primary add-to-cart-btn';
        if ($isUnavailable) {
            $buttonClasses .= ' disabled';
        }
        $buttonTitle = $isUnavailable ? 'Product unavailable for purchase' : 'Add to cart';
        ?>
        <div class="col mb-4">
            <div class="<?= htmlspecialchars($cardClasses, ENT_QUOTES) ?>" data-slug="<?= htmlspecialchars($slug) ?>" data-id="<?= htmlspecialchars($product['lpa_stock_ID']) ?>" data-status="<?= htmlspecialchars($status) ?>">
                <img src="<?= htmlspecialchars(getProductImageUrl($product['lpa_stock_image'] ?? '') ?? '') ?>" alt="<?= ($product['lpa_stock_name'] ?? '') ?>" loading="lazy">

                <div class="product-card-description">
                    <h3 class="text-truncate"><?= htmlspecialchars($product['lpa_stock_name']) ?></h3>
                    <div class="type-category-text">
                        <p class="text-truncate">
                            Category: <strong><?= $product['lpa_category_name'] ?></strong><br>
                        </p>
                        <p>*</p>
                        <p class="text-truncate">
                            Type: <strong><?= $product['lpa_type_name'] ?></strong>
                        </p>
                    </div>
                    <div class="product-card-add-cart">
                        <div class="price fw-bolder">$<?= number_format($product['lpa_stock_price'], 2) ?> AUD</div>
                        <button data-product-id="<?= htmlspecialchars($product['lpa_stock_ID']); ?>"
                                onclick="addToCartBtn(event)"
                                class="<?= htmlspecialchars($buttonClasses, ENT_QUOTES) ?>"
                                <?= $isUnavailable ? 'disabled aria-disabled="true"' : '' ?>
                                title="<?= htmlspecialchars($buttonTitle) ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            Add
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach;
    return ob_get_clean();
}

$productsHtml   = renderProducts($products);
$paginationHtml = renderPagination($pageNum, $totalPages, $_GET);

if ($isAjax) {
    $activeLabel = (!empty($selectedFilters['category']) || !empty($selectedFilters['type'])) ? 'Filtered' : 'All';
    echo json_encode([
        'html'       => $productsHtml,
        'pagination' => $paginationHtml,
        'label'      => $activeLabel,
        'count'      => count($products),
    ]);
    return;
}
?>
<form method="GET" id="filter-form" action="index.php">
    <input type="hidden" name="route" value="products">
    <div class="categories">
        <div class="filter-panel">
            <h2 class="filter-main-title">Filter</h2>

            <div class="filter-group">
                <h3 class="filter-title">Categories of Peripherals</h3>
                <?php $selectedCategories = $selectedFilters['category'] ?? []; ?>
                <?php foreach ($categories as $category): ?>
                    <label class="filter-option">
                        <input type="checkbox" name="category[]" value="<?= $category['lpa_category_ID'] ?>"
                            <?= in_array((int)$category['lpa_category_ID'], $selectedCategories, true) ? 'checked' : '' ?>>
                        <?= htmlspecialchars($category['lpa_category_name']) ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="filter-group">
                <h3 class="filter-title">Price</h3>
                <div class="price-inputs">
                    <input type="number" name="min_price" min="20" minlength="2" placeholder="Min" value="<?= $selectedFilters['min_price'] ?? '' ?>" class="price-field">
                    <input type="number" name="max_price" min="50" maxlength="5" max="9999" placeholder="Max" value="<?= $selectedFilters['max_price'] ?? '' ?>" class="price-field">
                </div>
            </div>

            <div class="filter-group">
                <h3 class="filter-title">Types</h3>
                <?php $selectedTypes = $selectedFilters['type'] ?? []; ?>
                <?php foreach ($types as $type): ?>
                    <?php
                        $isAll = (int)$type['lpa_type_ID'] === 4;
                        $checked = $isAll
                            ? (empty($selectedTypes) || in_array(4, $selectedTypes, true))
                            : in_array((int)$type['lpa_type_ID'], $selectedTypes, true);
                    ?>
                    <label class="filter-option">
                        <input type="checkbox" name="type[]" value="<?= $type['lpa_type_ID'] ?>" <?= $checked ? 'checked' : '' ?> class="type-checkbox" data-type-id="<?= $type['lpa_type_ID'] ?>">
                        <?= htmlspecialchars($type['lpa_type_name']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="products-container container">
            <div class="products-toolbar">
                <div class="active-filter-label">
                    <?php $activeLabel = (!empty($selectedFilters['category']) || !empty($selectedFilters['type'])) ? 'Filtered' : 'All'; ?>
                    <span class="filter-tag <?= ($activeLabel === 'Filtered') ? 'is-active' : '' ?>" id="results-label">
                      <?= $activeLabel ?> (<?= count($products) ?> Results)
                    </span>
                </div>

                <div class="sort-dropdown">
                    <label for="sort">Sort by:</label>
                    <select id="sort" name="sort">
                        <option value="popular" <?= ($selectedFilters['sort'] ?? '') === 'popular' ? 'selected' : '' ?>>Popular</option>
                        <option value="price_low_high" <?= ($selectedFilters['sort'] ?? '') === 'price_low_high' ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="price_high_low" <?= ($selectedFilters['sort'] ?? '') === 'price_high_low' ? 'selected' : '' ?>>Price: High to Low</option>
                    </select>
                </div>
            </div>

            <div class="container p-0">
                <div id="products-list" class="product-grid row row-cols-1 row-cols-sm-2 row-cols-md-3 m-auto">
                    <?= $productsHtml ?>
                </div>
                <div class="mt-4" id="pagination">
                    <?= $paginationHtml ?>
                </div>
            </div>
        </div>
    </div>
</form>

