<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../bootstrap.php';

$page = isset($_GET['page_num']) && is_numeric($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
$size = 20;
$offset = ($page - 1) * $size;

$action = trim($_GET['action'] ?? '');
$userId = isset($_GET['user_id']) && is_numeric($_GET['user_id']) ? (int)$_GET['user_id'] : null;

$conn = Database::getConnection();

$conds = [];
$params = [];
if ($action !== '') { $conds[] = 'action = :action'; $params[':action'] = $action; }
if ($userId) { $conds[] = 'user_id = :uid'; $params[':uid'] = $userId; }
$where = $conds ? ('WHERE ' . implode(' AND ', $conds)) : '';

$totalStmt = $conn->prepare("SELECT COUNT(*) FROM lpa_audit_log {$where}");
$totalStmt->execute($params);
$total = (int)$totalStmt->fetchColumn();
$totalPages = (int)ceil(max(1, $total) / $size);

$sql = "SELECT id, user_id, action, entity_type, entity_id, method, path, ip, user_agent, meta, created_at
        FROM lpa_audit_log {$where}
        ORDER BY id DESC
        LIMIT :offset,:size";
$stmt = $conn->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':size', $size, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

function renderAuditRows(array $rows): string {
    ob_start();
    foreach ($rows as $r):
        $meta = json_decode($r['meta'] ?? 'null', true);
        $metaStr = $meta ? htmlspecialchars(json_encode($meta, JSON_UNESCAPED_SLASHES)) : '';
        ?>
        <tr>
            <td class="text-muted small">#<?= (int)$r['id'] ?></td>
            <td><?= htmlspecialchars($r['action']) ?></td>
            <td><?= htmlspecialchars($r['entity_type'] ?? '-') ?></td>
            <td><?= htmlspecialchars((string)($r['entity_id'] ?? '-')) ?></td>
            <td><?= htmlspecialchars($r['method']) ?></td>
            <td class="text-break" style="max-width:320px;"><?= htmlspecialchars($r['path']) ?></td>
            <td><?= htmlspecialchars($r['ip']) ?></td>
            <td class="text-truncate" style="max-width:320px;" title="<?= htmlspecialchars($r['user_agent']) ?>">UA</td>
            <td class="text-break" style="max-width:320px;" title="<?= $metaStr ?>"><?= $metaStr ? 'meta' : '' ?></td>
            <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($r['created_at']))) ?></td>
        </tr>
    <?php endforeach;
    return ob_get_clean();
}

function renderAdminPagination(int $current, int $totalPages): string
{
    if ($totalPages <= 1) return '';
    ob_start();
    echo '<nav><ul class="pagination pagination-sm" id="pagination">';
    for ($i = 1; $i <= $totalPages; $i++) {
        $active = $i === $current ? ' active' : '';
        echo "<li class='page-item$active'><a href='?page_num=$i' class='page-link'>$i</a></li>";
    }
    echo '</ul></nav>';
    return ob_get_clean();
}

$rowsHtml = $rows ? renderAuditRows($rows) : '<tr><td colspan="10" class="text-center py-4">No audit events.</td></tr>';
$paginationHtml = renderAdminPagination($page, $totalPages);

$title = 'Security';
ob_start();
?>
<div class="container-account">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Audit Log</h1>
        <form class="d-flex gap-2" method="get" action="/admin.security">
            <input type="hidden" name="page_num" value="1">
            <input type="text" class="form-control form-control-sm" name="action" placeholder="Action" value="<?= htmlspecialchars($action) ?>">
            <input type="number" class="form-control form-control-sm" name="user_id" placeholder="User ID" value="<?= htmlspecialchars((string)($userId ?? '')) ?>">
            <button class="btn btn-sm btn-primary" type="submit">Filter</button>
        </form>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Action</th>
                        <th>Entity</th>
                        <th>Entity ID</th>
                        <th>Method</th>
                        <th>Path</th>
                        <th>IP</th>
                        <th>Agent</th>
                        <th>Meta</th>
                        <th>When</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?= $rowsHtml ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                <?= $paginationHtml ?>
            </div>
        </div>
    </div>
</div>
<?php
$pageContent = ob_get_clean();
include 'includes/admin/layout.php';
