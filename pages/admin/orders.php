<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../bootstrap.php';
loadRepo('repositories/invoice/InvoiceRepository.php');

$repo = new InvoiceRepository();

$searchTerm   = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$pageNum  = isset($_GET['page_num']) && is_numeric($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
$pageSize = 10;
$offset   = ($pageNum - 1) * $pageSize;

$total      = $repo->countAll($searchTerm, $statusFilter);
$totalPages = (int)ceil($total / $pageSize);
$orders     = $repo->findPaginated($offset, $pageSize, $searchTerm, $statusFilter);

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function renderRows(array $orders): string
{
    ob_start();
    foreach ($orders as $order) { ?>
        <tr>
            <td><a href="/orders.show?slug=<?= htmlspecialchars($order['slug']) ?>" target="_blank"><?= htmlspecialchars($order['invoice_number']) ?></a></td>
            <td><?= htmlspecialchars($order['client_name']) ?></td>
            <td><?= htmlspecialchars(date('d M Y', strtotime($order['created_at']))) ?></td>
            <td>
                <select class="form-select form-select-sm order-status" data-order-id="<?= htmlspecialchars($order['id']) ?>">
                    <option value="A"<?= $order['status'] === 'A' ? ' selected' : '' ?>>Active</option>
                    <option value="P"<?= $order['status'] === 'P' ? ' selected' : '' ?>>Pending</option>
                    <option value="C"<?= $order['status'] === 'C' ? ' selected' : '' ?>>Cancelled</option>
                </select>
            </td>
            <td>AUD <?= number_format((float)$order['total_amount'], 2) ?></td>
            <td>
                <a href="/orders.show?slug=<?= htmlspecialchars($order['slug']) ?>" class="btn btn-sm btn-outline-primary" target="_blank">View</a>
            </td>
        </tr>
    <?php }
    return ob_get_clean();
}

function renderAdminPagination(int $current, int $totalPages): string
{
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

$rowsHtml = $orders
    ? renderRows($orders)
    : '<tr class="no-results"><td colspan="6" class="text-center py-4">No orders found.</td></tr>';
$paginationHtml = $totalPages > 1 ? renderAdminPagination($pageNum, $totalPages) : '';

if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode(['rows' => $rowsHtml, 'pagination' => $paginationHtml]);
    exit;
}

$title = 'Orders';
$adminJs = '../assets/js/admin_orders.js';
ob_start();
?>
<div class="container-account">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Orders</h1>
        <a href="/admin.exportOrders?search=<?= urlencode($searchTerm) ?>&status=<?= urlencode($statusFilter) ?>" class="btn btn-sm btn-outline-secondary" id="export-orders">Export</a>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between mb-3">
                <input type="text" id="order-search" class="form-control w-25" placeholder="Search by number or client" value="<?= htmlspecialchars($searchTerm) ?>">
                <select class="form-select w-25" id="status-filter">
                    <option value="">Status</option>
                    <option value="A"<?= $statusFilter === 'A' ? ' selected' : '' ?>>Active</option>
                    <option value="P"<?= $statusFilter === 'P' ? ' selected' : '' ?>>Pending</option>
                    <option value="C"<?= $statusFilter === 'C' ? ' selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Client</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody id="order-rows">
                    <?= $rowsHtml ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-3" id="pagination-container">
                <?= $paginationHtml ?>
            </div>
        </div>
    </div>
</div>
<?php
$pageContent = ob_get_clean();
include 'includes/admin/layout.php';

