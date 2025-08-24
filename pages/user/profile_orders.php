<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/pagination.php';

if (!isset($invoices)) {
    require_once __DIR__ . '/../../bootstrap.php';
    loadRepo('repositories/invoice/InvoiceRepository.php');
    $repo = new InvoiceRepository();
    $user = $_SESSION['user'] ?? null;
    $pageNum  = isset($_GET['page_num']) && is_numeric($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
    $pageSize = 10;
    if ($user) {
        $totalInvoices = $repo->countInvoicesByUser($user['id']);
        $totalPages    = (int)ceil($totalInvoices / $pageSize);
        $offset        = ($pageNum - 1) * $pageSize;
        $invoices      = $repo->getInvoicesByUser($user['id'], $offset, $pageSize);
    } else {
        $invoices   = [];
        $totalPages = 1;
    }
}

$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-semibold text-primary">Your Orders</h5>
</div>

<?php if (empty($invoices)): ?>
    <p class="text-muted">No orders found.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
            <tr>
                <th scope="col">Order</th>
                <th scope="col">Date</th>
                <th scope="col">Status</th>
                <th scope="col">Total</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($invoices as $order): ?>
                <tr>
                    <td>
                        <a href="/orders.show?id=<?= $h($order['id']) ?>" class="text-decoration-none" target="_blank">
                            <?= $h($order['invoice_number']) ?>
                        </a>
                    </td>
                    <td><?= $h(date('d M Y', strtotime($order['created_at']))) ?></td>
                    <td><?= $h($order['status']) ?></td>
                    <td>AUD <?= number_format((float)$order['total_amount'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= renderPagination($pageNum ?? 1, $totalPages ?? 1, $_GET); ?>
<?php endif; ?>
