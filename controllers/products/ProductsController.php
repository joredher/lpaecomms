<?php
require_once 'includes/config.php';
require_once 'includes/pagination.php';

class ProductsController
{
    public function filter(): void
    {
        header('Content-Type: application/json');
        $conn = Database::getConnection();

        $pageNum = isset($_POST['page_num']) && is_numeric($_POST['page_num']) ? (int)$_POST['page_num'] : 1;
        $pageSize = 9;
        $offset = ($pageNum - 1) * $pageSize;

        $productQuery = "SELECT s.*, c.lpa_category_name, t.lpa_type_name
            FROM lpa_stock s
            JOIN lpa_category c ON s.lpa_fk_category_ID = c.lpa_category_ID
            JOIN lpa_type t ON s.lpa_fk_type_ID = t.lpa_type_ID";

        $params = [];
        $conditions = [];

        $categoryFilter = $_POST['category'] ?? [];
        if (!is_array($categoryFilter)) $categoryFilter = [$categoryFilter];

        $typeFilter = $_POST['type'] ?? [];
        if (!is_array($typeFilter)) $typeFilter = [$typeFilter];
        $typeFilter = array_filter($typeFilter, fn($val) => $val !== '4');

        if (!empty($typeFilter)) {
            $placeholders = implode(',', array_fill(0, count($typeFilter), '?'));
            $conditions[] = "s.lpa_fk_type_ID IN ($placeholders)";
            $params = array_merge($params, $typeFilter);
        } elseif (!empty($categoryFilter)) {
            $placeholders = implode(',', array_fill(0, count($categoryFilter), '?'));
            $conditions[] = "s.lpa_fk_category_ID IN ($placeholders)";
            $params = array_merge($params, $categoryFilter);
        }

        if (isset($_POST['min_price']) && is_numeric($_POST['min_price'])) {
            $conditions[] = "s.lpa_stock_price >= ?";
            $params[] = $_POST['min_price'];
        }
        if (isset($_POST['max_price']) && is_numeric($_POST['max_price'])) {
            $conditions[] = "s.lpa_stock_price <= ?";
            $params[] = $_POST['max_price'];
        }

        if (!empty($conditions)) {
            $productQuery .= " WHERE " . implode(' AND ', $conditions);
        }

        $sort = $_POST['sort'] ?? '';
        $productQuery .= match ($sort) {
            'price_low_high' => " ORDER BY s.lpa_stock_price ASC",
            'price_high_low' => " ORDER BY s.lpa_stock_price DESC",
            default => " ORDER BY s.lpa_stock_ID DESC",
        };

        $countStmt = $conn->prepare($productQuery);
        $countStmt->execute($params);
        $productsAll = $countStmt->fetchAll();
        $totalProducts = count($productsAll);
        $totalPages = ceil($totalProducts / $pageSize);

        $productQuery .= " LIMIT $offset, $pageSize";
        $productStmt = $conn->prepare($productQuery);
        $productStmt->execute($params);
        $products = $productStmt->fetchAll();

        ob_start();
        foreach ($products as $product) {
            ?>
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
            <?php
        }
        $productsHtml = ob_get_clean();

        $paginationHtml = renderPagination($pageNum, $totalPages);

        $activeLabel = (!empty($categoryFilter) || !empty($typeFilter) || isset($_POST['min_price']) || isset($_POST['max_price'])) ? 'Filtered' : 'All';

        echo json_encode([
            'success' => true,
            'productsHtml' => $productsHtml,
            'paginationHtml' => $paginationHtml,
            'activeLabel' => $activeLabel,
            'resultCount' => $totalProducts,
        ]);
        exit;
    }
}
