<?php
require_once 'includes/config.php';
$conn = Database::getConnection();

// Get categories
$categoryStmt = $conn->prepare(/** @lang text */ "SELECT * FROM lpa_category");
$categoryStmt->execute();
$categories = $categoryStmt->fetchAll();

// Get types
$typeStmt = $conn->prepare(/** @lang text */ "SELECT * FROM lpa_type");
$typeStmt->execute();
$types = $typeStmt->fetchAll();

// Get products
$productQuery = /** @lang text */
    "SELECT 
        s.*,
        c.lpa_category_name, 
        t.lpa_type_name
     FROM lpa_stock s
     JOIN lpa_category c ON s.lpa_fk_category_ID = c.lpa_category_ID
     JOIN lpa_type t ON s.lpa_fk_type_ID = t.lpa_type_ID";
$params = array();
$conditions = array();

$categoryFilter = $_GET['category'] ?? [];
if (!is_array($categoryFilter)) $categoryFilter = [$categoryFilter];

$typeFilter = $_GET['type'] ?? [];
if (!is_array($typeFilter)) $typeFilter = [$typeFilter];

$typeFilter = array_filter($typeFilter, fn($val) => $val !== 4); // Remove 'All'

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

if (!empty($_GET['sort'])) {
    switch ($_GET['sort']) {
        case 'price_low_high':
            $productQuery .= /** @lang text */
                " ORDER BY s.lpa_stock_price ASC";
            break;
        case 'price_high_low':
            $productQuery .= /** @lang text */
                " ORDER BY s.lpa_stock_price DESC";
            break;
        case 'popular':
        default:
            // later we can add popularity logic
            $productQuery .= /** @lang text */
                " ORDER BY s.lpa_stock_ID DESC";
            break;
    }
} else {
    $productQuery .= " ORDER BY s.lpa_stock_ID ASC";
}

echo '<pre>';
print_r($_GET['type'] ?? 'no type');
print_r($_GET['category'] ?? 'no category');
echo '</pre>';

$productStmt = $conn->prepare($productQuery);
$productStmt->execute();
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
                            <?= (isset($_GET['category']) && in_array($category['lpa_category_ID'], $_GET['category'])) ? 'checked' : '' ?>>
                        <?= htmlspecialchars($category['lpa_category_name']) ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <!-- 🔹 Price -->
            <div class="filter-group">
                <h3 class="filter-title">Price</h3>
                <div class="price-inputs">
                    <input type="number" name="min_price" placeholder="Min" value="<?= $_GET['min_price'] ?? '' ?>" class="price-field">
                    <input type="number" name="max_price" placeholder="Max" value="<?= $_GET['max_price'] ?? '' ?>" class="price-field">
                </div>
            </div>

            <!-- 🔹 Types -->
            <div class="filter-group">
                <h3 class="filter-title">Types</h3>
                <?php foreach ($types as $type): ?>
                    <label class="filter-option">
                        <input type="checkbox" name="type[]" value="<?= $type['lpa_type_ID'] ?>"
                            <?php
                            $isAll = $type['lpa_type_ID'] == 4;
                            $userTypes = $_GET['type'] ?? [];

                            if (!is_array($userTypes)) {
                                $userTypes = [$userTypes];
                            }

                            echo ($isAll && empty($userTypes)) || (!$isAll && in_array($type['lpa_type_ID'], $userTypes))
                                ? 'checked' : '';
                            ?>>
                        <?= htmlspecialchars($type['lpa_type_name']) ?>
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
                  <?= $activeLabel ?>
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
                            <div class="product-card">
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
                                        <button class="btn">Add to Cart</button>
                                    </div>
                                </div>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
</form>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const filterForm = document.getElementById("filter-form");

        filterForm.querySelectorAll('input[type="checkbox"], input[type="number"]').forEach(input => {
            input.addEventListener('change', () => {
                filterForm.submit();
            });
        });

        const sortSelect = document.getElementById("sort");
        if (sortSelect) {
            sortSelect.addEventListener("change", () => {
                filterForm.submit();
            });
        }

        // 🧠 Handle logic for "All" type
        const typeCheckboxes = document.querySelectorAll('.type-checkbox');
        const allTypeCheckbox = Array.from(typeCheckboxes).find(el => el.dataset.typeId === '4');

        typeCheckboxes.forEach(cb => {
            cb.addEventListener('change', () => {
                const anyChecked = Array.from(typeCheckboxes).some(input => input.checked && input.dataset.typeId !== '4');

                allTypeCheckbox.checked = !anyChecked;
            });
        });
    });
</script>