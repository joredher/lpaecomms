<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../bootstrap.php';
loadRepo('repositories/UserRepository.php');

$repo = new UserRepository();
$groups = $repo->getGroups();
$toastMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['lpa_users_ID'] ?? null;
    $data = [
        'lpa_user_username'   => trim($_POST['username'] ?? ''),
        'lpa_user_email'      => trim($_POST['email'] ?? ''),
        'lpa_user_firstname'  => trim($_POST['firstname'] ?? ''),
        'lpa_user_lastname'   => trim($_POST['lastname'] ?? ''),
        'lpa_fk_user_group_ID'=> (int)($_POST['group_id'] ?? 2),
        'lpa_user_status'     => trim($_POST['status'] ?? 'A'),
    ];
    $password = trim($_POST['password'] ?? '');
    if ($password !== '') {
        $data['lpa_user_password'] = password_hash($password, PASSWORD_DEFAULT);
    }
    if ($id) {
        $repo->update($id, $data);
        header('Location: /admin.users?status=updated');
    } else {
        $repo->create($data);
        header('Location: /admin.users?status=created');
    }
    exit;
}

if (isset($_GET['delete'])) {
    $repo->delete((int)$_GET['delete'], 'lpa_users_ID');
    header('Location: /admin.users');
    exit;
}

if (isset($_GET['status'])) {
    $toastMessage = $_GET['status'] === 'updated' ? 'User updated' : 'User created';
}

$searchTerm   = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$pageNum  = isset($_GET['page_num']) && is_numeric($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
$pageSize = 10;
$offset   = ($pageNum - 1) * $pageSize;
$total    = $repo->countAll($searchTerm, $statusFilter);
$totalPages = (int)ceil($total / $pageSize);
$users = $repo->findPaginated($offset, $pageSize, $searchTerm, $statusFilter);

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function renderRows(array $users): string {
    ob_start();
    foreach ($users as $user) {
        ?>
        <tr data-status="<?= htmlspecialchars($user['lpa_user_status'], ENT_QUOTES) ?>">
            <td class="user-username"><?= htmlspecialchars($user['lpa_user_username']) ?></td>
            <td class="user-email"><?= htmlspecialchars($user['lpa_user_email']) ?></td>
            <td><?= htmlspecialchars($user['group_name']) ?></td>
            <td class="user-status"><?= htmlspecialchars(statusLabel($user['lpa_user_status'])) ?></td>
            <td>
                <button type="button" class="btn btn-sm btn-primary text-white me-1 edit-user"
                        data-id="<?= $user['lpa_users_ID'] ?>"
                        data-username="<?= htmlspecialchars($user['lpa_user_username'], ENT_QUOTES) ?>"
                        data-email="<?= htmlspecialchars($user['lpa_user_email'], ENT_QUOTES) ?>"
                        data-firstname="<?= htmlspecialchars($user['lpa_user_firstname'], ENT_QUOTES) ?>"
                        data-lastname="<?= htmlspecialchars($user['lpa_user_lastname'], ENT_QUOTES) ?>"
                        data-group="<?= htmlspecialchars($user['lpa_fk_user_group_ID'], ENT_QUOTES) ?>"
                        data-status="<?= htmlspecialchars($user['lpa_user_status'], ENT_QUOTES) ?>">
                    <i class="bi bi-pencil"></i>
                </button>
                <a href="/admin.users?delete=<?= $user['lpa_users_ID'] ?>"
                   class="btn btn-sm btn-danger text-white delete-user"
                   data-name="<?= htmlspecialchars($user['lpa_user_username'], ENT_QUOTES) ?>"><i class="bi bi-trash"></i></a>
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

$rowsHtml = $users
    ? renderRows($users)
    : '<tr class="no-results"><td colspan="5" class="text-center py-4">No users found.</td></tr>';
$paginationHtml = $totalPages > 1 ? renderAdminPagination($pageNum, $totalPages) : '';

if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode(['rows' => $rowsHtml, 'pagination' => $paginationHtml]);
    exit;
}

function statusLabel($code) {
    return $code === 'A' ? 'Active' : 'Inactive';
}

$title = 'Users';
$adminJs = '../assets/js/admin_users.js';
ob_start();
?>
<div class="container-account">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Users</h1>
        <button id="add-user" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-plus-lg"></i>
            <span>Add User</span>
        </button>
    </div>

    <div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="user-form" action="/admin.users" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="lpa_users_ID" id="user-id">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" name="username" id="user-username">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" id="user-email">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" name="firstname" id="user-firstname">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" name="lastname" id="user-lastname">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" name="password" id="user-password">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Group</label>
                                <select class="form-select" name="group_id" id="user-group">
                                    <?php foreach ($groups as $group): ?>
                                        <option value="<?= $group['lpa_user_group_ID'] ?>">
                                            <?= htmlspecialchars($group['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" id="user-status">
                                    <option value="A">Active</option>
                                    <option value="I">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Save User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="bg-white rounded shadow-sm p-4">
        <div class="d-flex justify-content-between mb-3">
            <input type="text" id="user-search" class="form-control w-25" placeholder="Search by name or email">
            <select class="form-select w-25" id="status-filter">
                <option value="">Status</option>
                <option value="A"<?= $statusFilter === 'A' ? ' selected' : '' ?>>Active</option>
                <option value="I"<?= $statusFilter === 'I' ? ' selected' : '' ?>>Inactive</option>
            </select>
        </div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Group</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody id="user-rows">
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
