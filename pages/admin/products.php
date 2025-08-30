<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../bootstrap.php';
loadRepo('repositories/ProductRepository.php');

$repo = new ProductRepository();
$categories = $repo->getCategories();
$toastMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qty = isset($_POST['onhand']) ? (int)$_POST['onhand'] : 0;
    $qty = max(0, min(999, $qty));

    $rawPrice = preg_replace('/[^\d.]/', '', $_POST['price'] ?? '0');
    $price = (float)$rawPrice;
    $price = max(0, min(999999, $price));

    $currentImage = trim($_POST['current_image'] ?? '');
    $imageName = $currentImage;

    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        if (!is_dir(PRODUCT_IMAGE_PATH) && !mkdir($concurrentDirectory = PRODUCT_IMAGE_PATH, 0777, true) && !is_dir($concurrentDirectory)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $imageName = uniqid('prod_', true) . ($ext ? ".{$ext}" : '');
        move_uploaded_file($_FILES['image']['tmp_name'], PRODUCT_IMAGE_PATH . $imageName);
    }

    $data = [
        'name'        => trim($_POST['name'] ?? ''),
        'desc'        => trim($_POST['desc'] ?? ''),
        'features'    => trim($_POST['features'] ?? ''),
        'onhand'      => $qty,
        'price'       => $price,
        'image'       => $imageName,
        'status'      => trim($_POST['status'] ?? 'P'),
        'publish_at'  => !empty($_POST['publish_at']) ? date('Y-m-d H:i:s', strtotime($_POST['publish_at'])) : null,
        'category_id' => trim($_POST['category_id'] ?? 0),
        'type_id'     => trim($_POST['type_id'] ?? 0),
    ];
    $id = $_POST['lpa_stock_ID'] ?? null;
    if ($id) {
        $repo->updateProduct($id, $data);
        header('Location: /admin.products?status=updated');
    } else {
        $repo->createProduct($data);
        header('Location: /admin.products?status=created');
    }
    exit;
}

if (isset($_GET['delete'])) {
    $repo->delete((int)$_GET['delete'], 'lpa_stock_ID');
    header('Location: /admin.products');
    exit;
}

if (isset($_GET['status'])) {
    $toastMessage = $_GET['status'] === 'updated' ? 'Product updated' : 'Product created';
}

$searchTerm   = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$pageNum  = isset($_GET['page_num']) && is_numeric($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
$pageSize = 10;
$offset   = ($pageNum - 1) * $pageSize;
$total    = $repo->countAll($searchTerm, $statusFilter);
$totalPages = (int)ceil($total / $pageSize);
$products = $repo->findPaginated($offset, $pageSize, $searchTerm, $statusFilter);

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function renderRows(array $products): string {
    ob_start();
    foreach ($products as $product) {
        ?>
        <tr data-status="<?= htmlspecialchars($product['lpa_stock_status'], ENT_QUOTES) ?>">
            <td class="product-title"><?= htmlspecialchars($product['lpa_stock_name']) ?></td>
            <td class="product-sku"><?= htmlspecialchars($product['lpa_invitem_inv_no']) ?></td>
            <td><?= htmlspecialchars($product['available']) ?></td>
            <td class="product-price"><?= htmlspecialchars($product['lpa_stock_price']) ?></td>
            <td class="product-status"><?= htmlspecialchars(statusLabel($product['lpa_stock_status'])) ?></td>
            <td>
                <button type="button" class="btn btn-sm btn-primary text-white me-1 edit-product" data-bs-toggle="tooltip" title="Edit"
                        data-id="<?= $product['lpa_stock_ID'] ?>"
                        data-name="<?= htmlspecialchars($product['lpa_stock_name'], ENT_QUOTES) ?>"
                        data-desc="<?= htmlspecialchars($product['lpa_stock_desc'] ?? '', ENT_QUOTES) ?>"
                        data-features="<?= htmlspecialchars($product['lpa_stock_features'] ?? '', ENT_QUOTES) ?>"
                        data-qty="<?= htmlspecialchars($product['lpa_stock_onhand'], ENT_QUOTES) ?>"
                        data-price="<?= htmlspecialchars($product['lpa_stock_price'], ENT_QUOTES) ?>"
                        data-image="<?= htmlspecialchars($product['lpa_stock_image'] ?? '', ENT_QUOTES) ?>"
                        data-status="<?= htmlspecialchars($product['lpa_stock_status'], ENT_QUOTES) ?>"
                        data-category="<?= htmlspecialchars($product['lpa_fk_category_ID'], ENT_QUOTES) ?>"
                        data-type="<?= htmlspecialchars($product['lpa_fk_type_ID'], ENT_QUOTES) ?>"
                        data-publish-at="<?= htmlspecialchars($product['lpa_stock_publish_at'] ?? '', ENT_QUOTES) ?>"><i class="bi bi-pencil"></i></button>
                <a href="/admin.products?delete=<?= $product['lpa_stock_ID'] ?>" class="btn btn-sm btn-danger text-white delete-product" data-bs-toggle="tooltip" title="Delete" data-name="<?= htmlspecialchars($product['lpa_stock_name'], ENT_QUOTES) ?>"><i class="bi bi-trash"></i></a>
            </td>
        </tr>
        <?php
    }
    return ob_get_clean();
}

function renderAdminPagination(int $current, int $totalPages): string {
    if ($totalPages <= 1) return '';
    ob_start();
    echo '<nav><ul class="pagination pagination-sm" id="pagination">';
    for ($i = 1; $i <= $totalPages; $i++) {
        $active = $i === $current ? ' active' : '';
        echo "<li class='page-item$active'><a href='#' class='page-link' data-page='$i'>$i</a></li>";
    }
    echo '</ul></nav>';
    return ob_get_clean();
}

$rowsHtml = $products
    ? renderRows($products)
    : '<tr class="no-results"><td colspan="6" class="text-center py-4">No products found.</td></tr>';
$paginationHtml = $totalPages > 1 ? renderAdminPagination($pageNum, $totalPages) : '';

if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode(['rows' => $rowsHtml, 'pagination' => $paginationHtml]);
    exit;
}

function statusLabel($code) {
    return match ($code) {
        'P', 'A' => 'Published',
        'S'       => 'Scheduled',
        'U', 'I'  => 'Unpublished',
        default   => 'Unpublished',
    };
}

$title = 'Products';
$adminJs = '../assets/js/admin_products.js';
ob_start();
?>
<div class="container-account">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Products</h1>
        <button id="add-product" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-plus-lg"></i>
            <span>Add Product</span>
        </button>
    </div>

    <div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="product-form" action="/admin.products" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="lpa_stock_ID" id="product-id">
                        <input type="hidden" name="current_image" id="current-image">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label">Product Name</label>
                                <input type="text" class="form-control" name="name" id="product-name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Quantity</label>
                                <input type="number" class="form-control" name="onhand" id="product-qty" min="0" max="999">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Price</label>
                                <input type="text" class="form-control" name="price" id="product-price" inputmode="decimal">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Category</label>
                                <select class="form-select" name="category_id" id="product-category">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['lpa_category_ID'] ?>">
                                            <?= htmlspecialchars($category['lpa_category_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Type</label>
                                <select class="form-select" name="type_id" id="product-type">
                                    <option value="">Select Type</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="desc" id="product-desc" rows="5" cols="100"></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Features</label>
                                <textarea class="form-control" name="features" id="product-features" rows="5" cols="100"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Image</label>
                                <input type="file" class="form-control" name="image" id="product-image" accept="image/*">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" id="product-status">
                                    <option value="P">Published</option>
                                    <option value="S">Scheduled</option>
                                    <option value="U">Unpublished</option>
                                </select>
                            </div>
                            <div class="col-md-6" id="publish-at-group" style="display:none;">
                                <label class="form-label">Publish At</label>
                                <input type="datetime-local" class="form-control" name="publish_at" id="product-publish-at">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Save Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="bg-white rounded shadow-sm p-4">
        <div class="d-flex justify-content-between mb-3">
            <input type="text" id="product-search" class="form-control w-25" placeholder="Search by name, SKU or price">
            <select class="form-select w-25" id="status-filter">
                <option value="">Status</option>
                <option value="P"<?= $statusFilter === 'P' ? ' selected' : '' ?>>Published</option>
                <option value="S"<?= $statusFilter === 'S' ? ' selected' : '' ?>>Scheduled</option>
                <option value="U"<?= $statusFilter === 'U' ? ' selected' : '' ?>>Unpublished</option>
            </select>
        </div>
        <div class="table-responsive">
            <table id="products-table" class="table align-middle">
                <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Stock</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody id="product-rows">
                <?= $rowsHtml ?>
                </tbody>
            </table>
        </div>
        <div class="mt-3" id="pagination-container">
            <?= $paginationHtml ?>
        </div>
    </div>
<?php if (!empty($toastMessage)): ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        showToast({ message: <?= json_encode($toastMessage) ?>, type: 'success' });
    });
</script>
<?php endif; ?>
</div>
<?php
$pageContent = ob_get_clean();
include 'includes/admin/layout.php';
