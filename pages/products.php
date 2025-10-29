<?php
require_once 'includes/config.php';
require_once 'includes/pagination.php';
loadRepo('repositories/ProductRepository.php');
$conn = Database::getConnection();
$productRepo = new ProductRepository();
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Fetch categories and types
$categoryStmt = $conn->prepare(/** @lang text */ "SELECT * FROM lpa_category");
$categoryStmt->execute();
$categories = $categoryStmt->fetchAll();

$typeStmt = $conn->prepare(/** @lang text */ "SELECT * FROM lpa_type");
$typeStmt->execute();
$types = $typeStmt->fetchAll();

$pageNum = isset($_GET['page_num']) && is_numeric($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
$pageSize = 9;
$offset   = ($pageNum - 1) * $pageSize;

// Build product query
$productQuery = /** @lang text */
    "SELECT s.*, c.lpa_category_name, t.lpa_type_name
                 FROM lpa_stock s
                 JOIN lpa_category c ON s.lpa_fk_category_ID = c.lpa_category_ID
                 JOIN lpa_type t ON s.lpa_fk_type_ID = t.lpa_type_ID";
$params     = [];
$conditions = ["(s.lpa_stock_status IN ('P','A','D') OR (s.lpa_stock_status = 'S' AND s.lpa_stock_publish_at <= NOW()))"];

// Text search (from home hero or querystring)
$q = trim($_GET['q'] ?? ($_GET['query'] ?? ''));
if ($q !== '') {
    $conditions[] = "(s.lpa_stock_name LIKE ? OR c.lpa_category_name LIKE ? OR t.lpa_type_name LIKE ?)";
    $like = "%$q%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$categoryFilter = $_GET['category'] ?? [];
if (!is_array($categoryFilter)) $categoryFilter = [$categoryFilter];

$typeFilter = $_GET['type'] ?? [];
if (!is_array($typeFilter)) $typeFilter = [$typeFilter];
$typeFilter = array_filter($typeFilter, fn($val) => $val !== '4');

if (!empty($typeFilter)) {
    $placeholders = implode(',', array_fill(0, count($typeFilter), '?'));
    $conditions[] = "s.lpa_fk_type_ID IN ($placeholders)";
    $params      = array_merge($params, $typeFilter);
} elseif (!empty($categoryFilter)) {
    $placeholders = implode(',', array_fill(0, count($categoryFilter), '?'));
    $conditions[] = "s.lpa_fk_category_ID IN ($placeholders)";
    $params      = array_merge($params, $categoryFilter);
}

if (isset($_GET['min_price']) && is_numeric($_GET['min_price'])) {
    $conditions[] = "s.lpa_stock_price >= ?";
    $params[]     = $_GET['min_price'];
}
if (isset($_GET['max_price']) && is_numeric($_GET['max_price'])) {
    $conditions[] = "s.lpa_stock_price <= ?";
    $params[]     = $_GET['max_price'];
}

if (!empty($conditions)) {
    $productQuery .= " WHERE " . implode(" AND ", $conditions);
}

$productQuery .= match ($_GET['sort'] ?? '') {
    'price_low_high' => " ORDER BY s.lpa_stock_price ASC",
    'price_high_low' => " ORDER BY s.lpa_stock_price DESC",
    default => " ORDER BY s.lpa_stock_ID DESC",
};

$countStmt = $conn->prepare($productQuery);
$countStmt->execute($params);
$totalProducts = count($countStmt->fetchAll());
$totalPages    = ceil($totalProducts / $pageSize);

$productQuery .= " LIMIT $offset, $pageSize";
$productStmt = $conn->prepare($productQuery);
$productStmt->execute($params);
$products = $productStmt->fetchAll();

foreach ($products as &$product) {
    $product['lpa_stock_slug'] = $productRepo->ensureSlug((int)$product['lpa_stock_ID'], $product['lpa_stock_name'] ?? '');
}
unset($product);

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
                        <div class="price fw-bolder" data-price-aud="<?= number_format((float)$product['lpa_stock_price'], 2, '.', '') ?>">$<?= number_format((float)$product['lpa_stock_price'], 2) ?> AUD</div>
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
    $activeLabel = (!empty($_GET['q']) || !empty($_GET['query']))
        ? 'Search'
        : ((!empty($_GET['category']) || !empty($_GET['type'])) ? 'Filtered' : 'All');
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
        <div class="filter-panel" id="filters">
            <h2 class="filter-main-title">Filter</h2>

            <div class="filter-group">
                <h3 class="filter-title">Categories of Peripherals</h3>
                <?php foreach ($categories as $category): ?>
                    <label class="filter-option">
                        <input type="checkbox" name="category[]" value="<?= $category['lpa_category_ID'] ?>"
                            <?= (isset($_GET['category']) && in_array($category['lpa_category_ID'], $_GET['category'])) ? 'checked' : '' ?>>
                        <?= htmlspecialchars($category['lpa_category_name']) ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="filter-group">
                <h3 class="filter-title">Price</h3>
                <div class="price-inputs">
                    <input type="number" name="min_price" min="20" minlength="2" placeholder="Min" value="<?= $_GET['min_price'] ?? '' ?>" class="price-field">
                    <input type="number" name="max_price" min="50" maxlength="5" max="9999" placeholder="Max" value="<?= $_GET['max_price'] ?? '' ?>" class="price-field">
                </div>
            </div>

            <div class="filter-group">
                <h3 class="filter-title">Types</h3>
                <?php foreach ($types as $type): ?>
                    <?php
                        $isAll    = $type['lpa_type_ID'] == 4;
                        $userTypes = $_GET['type'] ?? [];
                        if (!is_array($userTypes)) $userTypes = [$userTypes];
                        $checked = ($isAll && empty($userTypes)) || (!$isAll && in_array($type['lpa_type_ID'], $userTypes));
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
                <button type="button" class="btn btn-outline-light d-md-none" id="toggle-filters" aria-expanded="false" aria-controls="filters">
                    Filters
                </button>
                <div class="active-filter-label">
                    <?php
                    $activeLabel = (!empty($_GET['q']) || !empty($_GET['query']))
                        ? 'Search'
                        : ((!empty($_GET['category']) || !empty($_GET['type'])) ? 'Filtered' : 'All');
                    ?>
                    <span class="filter-tag <?= ($activeLabel !== 'All') ? 'is-active' : '' ?>" id="results-label">
                      <?= $activeLabel ?> (<?= count($products) ?> Results)
                    </span>
                </div>

                <div class="sort-dropdown">
                    <label for="sort">Sort by:</label>
                    <select id="sort" name="sort">
                        <option value="popular" <?= ($_GET['sort'] ?? '') === 'popular' ? 'selected' : '' ?>>Popular</option>
                        <option value="price_low_high" <?= ($_GET['sort'] ?? '') === 'price_low_high' ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="price_high_low" <?= ($_GET['sort'] ?? '') === 'price_high_low' ? 'selected' : '' ?>>Price: High to Low</option>
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

