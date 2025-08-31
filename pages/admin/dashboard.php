<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../bootstrap.php';
loadRepo('repositories/UserRepository.php');

$pdo = Database::getConnection();
$userRepo = new UserRepository();

// Sales over time
$salesStmt = $pdo->query("SELECT DATE(i.lpa_inv_date) AS inv_date, SUM(ii.lpa_invitem_qty) AS qty
    FROM lpa_invoice_items ii
    JOIN lpa_invoices i ON i.lpa_invoices_ID = ii.lpa_fk_invoices_ID
    GROUP BY DATE(i.lpa_inv_date)
    ORDER BY DATE(i.lpa_inv_date)");
$salesRows = $salesStmt->fetchAll(PDO::FETCH_ASSOC);

// Orders last 7 days
$weekAgo = (new DateTime('-7 days'))->format('Y-m-d');
$statusStmt = $pdo->prepare("SELECT lpa_inv_status, COUNT(*) AS cnt
    FROM lpa_invoices WHERE DATE(lpa_inv_date) >= :date GROUP BY lpa_inv_status");
$statusStmt->execute([':date' => $weekAgo]);
$statusRows = $statusStmt->fetchAll(PDO::FETCH_KEY_PAIR);
$completedCount = $statusRows['A'] ?? 0;
$pendingCount = ($statusRows['P'] ?? 0) + ($statusRows['C'] ?? 0);
$totalWeek = $completedCount + $pendingCount;

// New users
$newUsersCount = $userRepo->countUsersSince($weekAgo . ' 00:00:00');

// Paid vs unpaid orders
$paidStmt = $pdo->query("SELECT
    SUM(CASE WHEN lpa_inv_status = 'A' THEN 1 ELSE 0 END) AS paid,
    SUM(CASE WHEN lpa_inv_status <> 'A' THEN 1 ELSE 0 END) AS unpaid
    FROM lpa_invoices");
$paidCounts = $paidStmt->fetch(PDO::FETCH_ASSOC);

$title = 'Store Dashboard';
$adminJs = '../assets/js/admin_dashboard.js';

ob_start();
?>
<h1 class="mb-4">Store Dashboard</h1>
<div class="row g-4">
    <div class="col-12 col-lg-8">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Products Sold Over Time</h5>
                <canvas id="salesChart" height="150"></canvas>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Orders (Last 7 Days)</h5>
                <div class="progress" style="height:6px;">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $totalWeek ? ($completedCount/$totalWeek*100) : 0 ?>%"></div>
                    <div class="progress-bar bg-light" role="progressbar" style="width: <?= $totalWeek ? ($pendingCount/$totalWeek*100) : 0 ?>%"></div>
                </div>
                <div class="d-flex justify-content-around mt-3">
                    <span class="d-flex align-items-center" title="Completed">
                        <span class="bg-primary rounded-circle me-2" style="width:10px;height:10px;"></span>
                        <?= $completedCount ?>
                    </span>
                    <span class="d-flex align-items-center" title="Pending">
                        <span class="bg-light border rounded-circle me-2" style="width:10px;height:10px;"></span>
                        <?= $pendingCount ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card text-center h-100">
            <div class="card-body d-flex flex-column justify-content-center">
                <h5 class="card-title">New Users (7 days)</h5>
                <p class="display-6 mb-0"><?= $newUsersCount ?></p>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-8">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Coupon Usage</h5>
                <canvas id="couponChart" height="150"></canvas>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Paid vs Unpaid Orders</h5>
                <canvas id="paymentChart" height="150"></canvas>
            </div>
        </div>
    </div>
    <script>
        window.dashboardData = {
            sales: <?= json_encode($salesRows) ?>,
            orderStatus: {completed: <?= $completedCount ?>, pending: <?= $pendingCount ?>},
            payments: <?= json_encode($paidCounts) ?>
        };
    </script>
</div>
<?php
$pageContent = ob_get_clean();
include 'includes/admin/layout.php';
