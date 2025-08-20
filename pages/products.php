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
?>

<form id="filter-form">
    <div class="categories">
        <div class="filter-panel">
            <h2 class="filter-main-title">Filter</h2>

            <!-- 🔹 Categories -->
            <div class="filter-group">
                <h3 class="filter-title">Categories of Peripherals</h3>
                <?php foreach ($categories as $category): ?>
                    <label class="filter-option">
                        <input type="checkbox" name="category[]" value="<?= $category['lpa_category_ID'] ?>">
                        <?= htmlspecialchars($category['lpa_category_name']) ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <!-- 🔹 Price -->
            <div class="filter-group">
                <h3 class="filter-title">Price</h3>
                <div class="price-inputs">
                    <input type="number" name="min_price" min="20" minlength="2" placeholder="Min" class="price-field">
                    <input type="number" name="max_price" min="50" maxlength="5" max="9999" placeholder="Max" class="price-field">
                </div>
            </div>

            <!-- 🔹 Types -->
            <div class="filter-group">
                <h3 class="filter-title">Types</h3>
                <?php foreach ($types as $type): ?>
                    <?php $isAll = $type['lpa_type_ID'] == 4; ?>
                    <label class="filter-option">
                        <input type="checkbox" name="type[]" value="<?= $type['lpa_type_ID'] ?>" <?= $isAll ? 'checked' : '' ?> class="type-checkbox" data-type-id="<?= $type['lpa_type_ID'] ?>">
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
                    <span class="filter-tag">All (Loading...)</span>
                </div>

                <div class="sort-dropdown">
                    <label for="sort">Sort by:</label>
                    <select id="sort" name="sort">
                        <option value="popular" selected>Popular</option>
                        <option value="price_low_high">Price: Low to High</option>
                        <option value="price_high_low">Price: High to Low</option>
                    </select>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="container p-0">
                <div id="products-grid" class="product-grid row row-cols-1 row-cols-md-2 row-cols-lg-3 m-auto"></div>
                <div id="pagination-container" class="mt-4"></div>
            </div>
        </div>
    </div>
</form>
