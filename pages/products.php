<?php
require_once 'includes/config.php';
require_once 'includes/pagination.php';
$conn = Database::getConnection();

// Get categories
$categoryStmt = $conn->prepare(/** @lang text */ "SELECT * FROM lpa_category");
$categoryStmt->execute();
$categories = $categoryStmt->fetchAll();

// Get types
$typeStmt = $conn->prepare(/** @lang text */ "SELECT * FROM lpa_type");
$typeStmt->execute();
$types = $typeStmt->fetchAll();

$pageNum = isset($_GET['page_num']) && is_numeric($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
$pageSize = 9;
$offset = ($pageNum - 1) * $pageSize;

// Get products
$productQuery = /** @lang text */
    "SELECT 
        s.*,
        c.lpa_category_name, 
        t.lpa_type_name
     FROM lpa_stock s
     JOIN lpa_category c ON s.lpa_fk_category_ID = c.lpa_category_ID
     JOIN lpa_type t ON s.lpa_fk_type_ID = t.lpa_type_ID";
$params = [];
$conditions = [];

$categoryFilter = $_GET['category'] ?? [];
if (!is_array($categoryFilter)) $categoryFilter = [$categoryFilter];

$typeFilter = $_GET['type'] ?? [];
if (!is_array($typeFilter)) $typeFilter = [$typeFilter];

$typeFilter = array_filter($typeFilter, fn($val) => $val !== '4'); // Remove 'All'

// Apply filters: If type is active, ignore category
if (!empty($typeFilter)) {
    $placeholders = implode(',', array_fill(0, count($typeFilter), '?'));
    $conditions[] = "s.lpa_fk_type_ID IN ($placeholders)";
    $params = array_merge($params, $typeFilter);
} elseif (!empty($categoryFilter)) {
    $placeholders = implode(',', array_fill(0, count($categoryFilter), '?'));
    $conditions[] = "s.lpa_fk_category_ID IN ($placeholders)";
    $params = array_merge($params, $categoryFilter);
}

// Price filter
if (isset($_GET['min_price']) && is_numeric($_GET['min_price'])) {
    $conditions[] = "s.lpa_stock_price >= ?";
    $params[] = $_GET['min_price'];
}
if (isset($_GET['max_price']) && is_numeric($_GET['max_price'])) {
    $conditions[] = "s.lpa_stock_price <= ?";
    $params[] = $_GET['max_price'];
}

// Combine conditions
if (!empty($conditions)) {
    $productQuery .= " WHERE " . implode(" AND ", $conditions);
}


$productQuery .= match ($_GET['sort'] ?? '') {
    'price_low_high' => " ORDER BY s.lpa_stock_price ASC",
    'price_high_low' => " ORDER BY s.lpa_stock_price DESC",
    default => " ORDER BY s.lpa_stock_ID DESC",
};

//echo '<pre>';
//print_r($_GET['type'] ?? 'no type');
//print_r($_GET['category'] ?? 'no category');
//echo '</pre>';

$countQuery = $productQuery;
$countStmt = $conn->prepare($countQuery);
$countStmt->execute($params);
$productsAll = $countStmt->fetchAll();
$totalProducts = count($productsAll);
$totalPages = ceil($totalProducts / $pageSize);

$productQuery .= " LIMIT $offset, $pageSize";
$productStmt = $conn->prepare($productQuery);
$productStmt->execute($params);
$products = $productStmt->fetchAll();
?>

<form method="GET" id="filter-form" action="index.php">
    <input type="hidden" name="page" value="categories">
    <div class="categories">
        <div class="filter-panel">
            <h2 class="filter-main-title">Filter</h2>

            <!-- 🔹 Categories -->
            <div class="filter-group">
                <h3 class="filter-title">Categories of Peripherals</h3>
                <?php foreach ($categories as $category): ?>
                    <label class="filter-option">
                        <input type="checkbox" name="category[]" value="<?= $category['lpa_category_ID'] ?>"
                            <?= (isset($_GET['category']) && in_array($category['lpa_category_ID'], $_GET['category']))
                                ? 'checked' : '' ?>>
                        <?= htmlspecialchars($category['lpa_category_name']) ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <!-- 🔹 Price -->
            <div class="filter-group">
                <h3 class="filter-title">Price</h3>
                <div class="price-inputs">
                    <input type="number" name="min_price" min="20" minlength="2" placeholder="Min" value="<?= $_GET['min_price'] ?? '' ?>" class="price-field">
                    <input type="number" name="max_price" min="50" maxlength="5" max="9999" placeholder="Max" value="<?= $_GET['max_price'] ?? '' ?>" class="price-field">
                </div>
            </div>

            <!-- 🔹 Types -->
            <div class="filter-group">
                <h3 class="filter-title">Types</h3>
                <?php foreach ($types as $type): ?>
                    <label class="filter-option">
                        <?php
                        $isAll = $type['lpa_type_ID'] == 4;
                        $userTypes = $_GET['type'] ?? [];
                        if (!is_array($userTypes)) $userTypes = [$userTypes];
                        $checked = ($isAll && empty($userTypes)) || (!$isAll && in_array($type['lpa_type_ID'], $userTypes));
                        ?>
                        <label class="filter-option">
                            <input type="checkbox" name="type[]" value="<?= $type['lpa_type_ID'] ?>" <?= $checked ? 'checked' : '' ?> class="type-checkbox" data-type-id="<?= $type['lpa_type_ID'] ?>">
                            <?= htmlspecialchars($type['lpa_type_name']) ?>
                        </label>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <!-- 🔹 RIGHT: Product Area -->
        <div class="products-container container">
            <!-- Top Toolbar -->
            <div class="products-toolbar">
                <div class="active-filter-label">
                    <?php
                    $activeLabel = (!empty($_GET['category']) || !empty($_GET['type'])) ? 'Filtered' : 'All';
                    ?>
                    <span class="filter-tag <?= ($activeLabel === 'Filtered') ? 'is-active' : '' ?>">
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

            <!-- Products Grid -->
            <div class="container p-0">
                <div class="product-grid row row-cols-3 m-auto">
                    <?php foreach ($products as $product): ?>
                        <div class="col mb-4">
                            <div class="product-card clickable-card ripple-container" data-id="<?= $product['lpa_stock_ID'] ?>">
                                <img src="assets/images/test-images/<?= htmlspecialchars($product['lpa_stock_image']) ?>" alt="<?= htmlspecialchars($product['lpa_stock_name']) ?>">

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
                                                class="btn btn-sm btn-outline-primary add-to-cart-btn">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                            Add
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-4">
                    <?= renderPagination(
                            $pageNum, $totalPages, $_GET
                    ) ?>
                </div>
            </div>
        </div>
    </div>
</form>