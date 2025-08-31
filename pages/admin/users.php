<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../bootstrap.php';
loadRepo('repositories/UserRepository.php');
require_once __DIR__ . '/../../helpers/mail.php';

$repo = new UserRepository();
$groups = $repo->getGroups();
$toastMessage = '';

if (isset($_GET['check_email'])) {
    $email = trim($_GET['check_email']);
    $excludeId = isset($_GET['id']) ? (int)$_GET['id'] : null;
    $exists = $repo->emailExists($email, $excludeId);
    header('Content-Type: application/json');
    echo json_encode(['exists' => $exists]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['lpa_users_ID'] ?? null;
    $data = [
        'lpa_user_username'   => trim($_POST['username'] ?? ''),
        'lpa_user_email'      => trim($_POST['email'] ?? ''),
        'lpa_user_firstname'  => trim($_POST['firstname'] ?? ''),
        'lpa_user_lastname'   => trim($_POST['lastname'] ?? ''),
        'lpa_fk_user_group_ID'=> (int)($_POST['group_id'] ?? 2),
    ];
    $emailUser = explode('@', $data['lpa_user_email'])[0] ?? '';
    $data['lpa_user_username'] = $emailUser . date('Y');
    if ($data['lpa_user_email'] === '' || !filter_var($data['lpa_user_email'], FILTER_VALIDATE_EMAIL) ||
        $data['lpa_user_firstname'] === '' || $data['lpa_user_lastname'] === '' ||
        $data['lpa_fk_user_group_ID'] === 0) {
        header('Location: /admin.users?status=missing_fields');
        exit;
    }
    if (preg_match('/\d/', $data['lpa_user_firstname']) || preg_match('/\d/', $data['lpa_user_lastname'])) {
        header('Location: /admin.users?status=invalid_name');
        exit;
    }
    if ($repo->emailExists($data['lpa_user_email'], $id ? (int)$id : null)) {
        header('Location: /admin.users?status=email_exists');
        exit;
    }
    if ($id) {
        $repo->update($id, $data);
        header('Location: /admin.users?status=updated');
    } else {
        $data['lpa_user_password'] = password_hash('stage123.', PASSWORD_DEFAULT);
        $data['lpa_user_status'] = 'I';
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

if (isset($_GET['activate'])) {
    $id = (int)$_GET['activate'];
    $user = $repo->findById($id);
    if ($user) {
        $repo->setStatus($id, 'A');

        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 day'));
        $repo->saveVerificationToken($id, $token, $expiresAt);

        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        $verificationUrl = "$baseUrl/verify?token=$token";

        sendVerificationEmail([
            'to' => $user['lpa_user_email'],
            'username' => $user['lpa_user_username'],
            'firstname' => $user['lpa_user_firstname'],
            'verificationUrl' => $verificationUrl
        ]);
    }
    header('Location: /admin.users?status=activated');
    exit;
}

if (isset($_GET['deactivate'])) {
    $id = (int)$_GET['deactivate'];
    $user = $repo->findById($id);
    if ($user) {
        $repo->setStatus($id, 'I');
        sendAccountDeactivationEmail([
            'email' => $user['lpa_user_email'],
            'username' => $user['lpa_user_username']
        ]);
    }
    header('Location: /admin.users?status=deactivated');
    exit;
}

if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'updated':
            $toastMessage = 'User updated';
            break;
        case 'created':
            $toastMessage = 'User created';
            break;
        case 'activated':
            $toastMessage = 'User activated';
            break;
        case 'deactivated':
            $toastMessage = 'User deactivated';
            break;
        case 'email_exists':
            $toastMessage = 'Email already exists';
            break;
        case 'invalid_name':
            $toastMessage = 'Names cannot contain numbers';
            break;
        case 'missing_fields':
            $toastMessage = 'Please fill in all required fields correctly';
            break;
    }
}

$searchTerm   = trim($_GET['search'] ?? '');
$pageNum  = isset($_GET['page_num']) && is_numeric($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
$pageSize = 10;
$offset   = ($pageNum - 1) * $pageSize;
$total    = $repo->countAll($searchTerm);
$totalPages = (int)ceil($total / $pageSize);
$users = $repo->findPaginated($offset, $pageSize, $searchTerm);

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function renderRows(array $users, int $currentId): string {
    ob_start();
    foreach ($users as $user) {
        $isSelf = (int)$user['lpa_users_ID'] === $currentId;
        ?>
        <tr data-status="<?= htmlspecialchars($user['lpa_user_status'], ENT_QUOTES) ?>">
            <td class="user-username"><?= htmlspecialchars($user['lpa_user_username']) ?></td>
            <td class="user-email"><?= htmlspecialchars($user['lpa_user_email']) ?></td>
            <td><?= htmlspecialchars($user['group_name']) ?></td>
            <td class="user-status"><?= htmlspecialchars(statusLabel($user['lpa_user_status'])) ?></td>
            <td class="text-center">
                <?php if ($isSelf): ?>
                    <i class="bi bi-check-circle-fill text-success"></i>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($isSelf): ?>
                    <a href="/profile" class="btn btn-sm btn-primary text-white me-1 icon-btn" data-bs-toggle="tooltip" title="My Account"><i class="bi bi-person"></i></a>
                <?php else: ?>
                    <button type="button" class="btn btn-sm btn-primary text-white me-1 edit-user icon-btn" data-bs-toggle="tooltip" title="Edit"
                            data-id="<?= $user['lpa_users_ID'] ?>"
                            data-username="<?= htmlspecialchars($user['lpa_user_username'], ENT_QUOTES) ?>"
                            data-email="<?= htmlspecialchars($user['lpa_user_email'], ENT_QUOTES) ?>"
                            data-firstname="<?= htmlspecialchars($user['lpa_user_firstname'], ENT_QUOTES) ?>"
                            data-lastname="<?= htmlspecialchars($user['lpa_user_lastname'], ENT_QUOTES) ?>"
                            data-group="<?= htmlspecialchars($user['lpa_fk_user_group_ID'], ENT_QUOTES) ?>"
                            data-status="<?= htmlspecialchars($user['lpa_user_status'], ENT_QUOTES) ?>">
                        <i class="bi bi-pencil"></i></button>
                    <a href="/admin.users?delete=<?= $user['lpa_users_ID'] ?>" class="btn btn-sm btn-danger text-white me-1 delete-user icon-btn" data-bs-toggle="tooltip" title="Delete" data-name="<?= htmlspecialchars($user['lpa_user_username'], ENT_QUOTES) ?>"><i class="bi bi-trash"></i></a>
                    <?php if ($user['lpa_user_status'] === 'I'): ?>
                        <a href="/admin.users?activate=<?= $user['lpa_users_ID'] ?>" class="btn btn-sm btn-success text-white me-1 activate-user icon-btn" data-bs-toggle="tooltip" title="Activate" data-name="<?= htmlspecialchars($user['lpa_user_username'], ENT_QUOTES) ?>"><i class="bi bi-person-check"></i></a>
                    <?php else: ?>
                        <a href="/admin.users?deactivate=<?= $user['lpa_users_ID'] ?>" class="btn btn-sm btn-warning text-white me-1 deactivate-user icon-btn" data-bs-toggle="tooltip" title="Deactivate" data-name="<?= htmlspecialchars($user['lpa_user_username'], ENT_QUOTES) ?>"><i class="bi bi-person-x"></i></a>
                    <?php endif; ?>
                <?php endif; ?>
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
    ? renderRows($users, (int)($_SESSION['user']['id'] ?? 0))
    : '<tr class="no-results"><td colspan="6" class="text-center py-4">No users found.</td></tr>';
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
                            <div class="col-md-12">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" name="username" id="user-username" readonly>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" id="user-email">
                                <div class="invalid-feedback" id="email-error"></div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" name="firstname" id="user-firstname">
                                <div class="invalid-feedback" id="firstname-error"></div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" name="lastname" id="user-lastname">
                                <div class="invalid-feedback" id="lastname-error"></div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Group</label>
                                <select class="form-select" name="group_id" id="user-group">
                                    <?php foreach ($groups as $group): ?>
                                        <option value="<?= $group['lpa_user_group_ID'] ?>">
                                            <?= htmlspecialchars($group['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
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
        </div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Group</th>
                    <th>Status</th>
                    <th>Logged In</th>
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
