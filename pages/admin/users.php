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
$toastType = 'success';
if (!empty($_SESSION['toast'])) {
    $toastMessage = $_SESSION['toast']['message'] ?? '';
    $toastType = $_SESSION['toast']['type'] ?? 'success';
    unset($_SESSION['toast']);
}

$formData = $_SESSION['user_form_data'] ?? [];
$formErrors = $_SESSION['user_form_errors'] ?? [];
$formIsEdit = $_SESSION['user_form_is_edit'] ?? false;
$shouldReopenForm = $_SESSION['user_form_open'] ?? false;
unset($_SESSION['user_form_data'], $_SESSION['user_form_errors'], $_SESSION['user_form_is_edit'], $_SESSION['user_form_open']);

$preserveFormState = static function (array $data, array $errors, bool $isEdit, ?array $toast = null): void {
    $_SESSION['user_form_data'] = $data;
    $_SESSION['user_form_errors'] = $errors;
    $_SESSION['user_form_is_edit'] = $isEdit;
    $_SESSION['user_form_open'] = true;
    if ($toast) {
        $_SESSION['toast'] = $toast;
    }
};

$buildErrorToast = static function (array $errors): array {
    $toast = ['message' => 'Please correct the highlighted fields', 'type' => 'danger'];
    if (isset($errors['email']) && $errors['email'] === 'Email already exists') {
        $toast['message'] = 'A user with this email already exists';
    } elseif (isset($errors['username']) && $errors['username'] === 'Username already exists') {
        $toast['message'] = 'This user is already registered';
    } elseif ((isset($errors['firstname']) && $errors['firstname'] === 'Names cannot contain numbers') ||
        (isset($errors['lastname']) && $errors['lastname'] === 'Names cannot contain numbers')) {
        $toast['message'] = 'Names cannot contain numbers';
    }
    return $toast;
};

$serverFormState = [
    'open' => (bool)$shouldReopenForm,
    'isEdit' => (bool)$formIsEdit,
    'data' => empty($formData) ? new stdClass() : $formData,
    'errors' => empty($formErrors) ? new stdClass() : $formErrors,
];

if (isset($_GET['check_email'])) {
    $email = trim($_GET['check_email']);
    $excludeId = isset($_GET['id']) ? (int)$_GET['id'] : null;
    $exists = $repo->emailExists($email, $excludeId);
    header('Content-Type: application/json');
    echo json_encode(['exists' => $exists]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['lpa_users_ID']) && $_POST['lpa_users_ID'] !== '' ? (int)$_POST['lpa_users_ID'] : null;
    $formInput = [
        'id' => $id,
        'username' => trim($_POST['username'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'firstname' => trim($_POST['firstname'] ?? ''),
        'lastname' => trim($_POST['lastname'] ?? ''),
        'group_id' => (int)($_POST['group_id'] ?? 2),
    ];

    if ($formInput['username'] === '' && $formInput['email'] !== '') {
        $emailUser = explode('@', $formInput['email'])[0] ?? '';
        if ($emailUser !== '') {
            $formInput['username'] = $emailUser . date('Y');
        }
    }

    $data = [
        'lpa_user_username'   => $formInput['username'],
        'lpa_user_email'      => $formInput['email'],
        'lpa_user_firstname'  => $formInput['firstname'],
        'lpa_user_lastname'   => $formInput['lastname'],
        'lpa_fk_user_group_ID'=> $formInput['group_id'],
    ];

    $errors = [];

    if ($formInput['email'] === '' || !filter_var($formInput['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please provide a valid email address';
    }

    if ($formInput['firstname'] === '') {
        $errors['firstname'] = 'First name is required';
    } elseif (preg_match('/\d/', $formInput['firstname'])) {
        $errors['firstname'] = 'Names cannot contain numbers';
    }

    if ($formInput['lastname'] === '') {
        $errors['lastname'] = 'Last name is required';
    } elseif (preg_match('/\d/', $formInput['lastname'])) {
        $errors['lastname'] = 'Names cannot contain numbers';
    }

    if ($formInput['group_id'] === 0) {
        $errors['group_id'] = 'Please select a group';
    }

    if (!isset($errors['email']) && $repo->emailExists($formInput['email'], $id)) {
        $errors['email'] = 'Email already exists';
    }

    if ($formInput['username'] === '') {
        $errors['username'] = 'A username is required';
    } elseif ($repo->usernameExists($formInput['username'], $id)) {
        $errors['username'] = 'Username already exists';
    }

    if (!empty($errors)) {
        $preserveFormState($formInput, $errors, $id !== null, $buildErrorToast($errors));
        header('Location: /admin.users');
        exit;
    }

    try {
        if ($id) {
            $repo->update($id, $data);
            $_SESSION['toast'] = ['message' => 'User updated', 'type' => 'success'];
        } else {
            $data['lpa_user_password'] = password_hash('stage123.', PASSWORD_DEFAULT);
            $data['lpa_user_status'] = 'I';
            $repo->create($data);
            $_SESSION['toast'] = ['message' => 'User created', 'type' => 'success'];
        }
    } catch (PDOException $e) {
        $duplicateToast = null;
        if ($e->getCode() === '23000') {
            $duplicateMessage = strtolower($e->getMessage());
            if (strpos($duplicateMessage, 'lpa_user_email') !== false) {
                $errors['email'] = 'Email already exists';
            } else {
                $errors['username'] = 'Username already exists';
            }
            $duplicateToast = $buildErrorToast($errors);
        } else {
            error_log('Failed to save user: ' . $e->getMessage());
            $duplicateToast = ['message' => 'An unexpected error occurred while saving the user', 'type' => 'danger'];
        }
        $preserveFormState($formInput, $errors, $id !== null, $duplicateToast);
        header('Location: /admin.users');
        exit;
    }

    header('Location: /admin.users');
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
    $_SESSION['toast'] = ['message' => 'User activated', 'type' => 'success'];
    header('Location: /admin.users');
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
    $_SESSION['toast'] = ['message' => 'User deactivated', 'type' => 'success'];
    header('Location: /admin.users');
    exit;
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
    <script>
        window.userFormState = <?= json_encode($serverFormState, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    </script>
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
                    <h5 class="modal-title"><?= $formIsEdit ? 'Edit User' : 'Add User' ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="user-form" action="/admin.users" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="lpa_users_ID" id="user-id" value="<?= htmlspecialchars($formData['id'] ?? '') ?>">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Username</label>
                                <input type="text"
                                       class="form-control<?= isset($formErrors['username']) ? ' is-invalid' : '' ?>"
                                       name="username"
                                       id="user-username"
                                       value="<?= htmlspecialchars($formData['username'] ?? '') ?>"
                                       readonly>
                                <div class="invalid-feedback" id="username-error"><?= htmlspecialchars($formErrors['username'] ?? '') ?></div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Email</label>
                                <input type="email"
                                       class="form-control<?= isset($formErrors['email']) ? ' is-invalid' : '' ?>"
                                       name="email"
                                       id="user-email"
                                       value="<?= htmlspecialchars($formData['email'] ?? '') ?>">
                                <div class="invalid-feedback" id="email-error"><?= htmlspecialchars($formErrors['email'] ?? '') ?></div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">First Name</label>
                                <input type="text"
                                       class="form-control<?= isset($formErrors['firstname']) ? ' is-invalid' : '' ?>"
                                       name="firstname"
                                       id="user-firstname"
                                       value="<?= htmlspecialchars($formData['firstname'] ?? '') ?>">
                                <div class="invalid-feedback" id="firstname-error"><?= htmlspecialchars($formErrors['firstname'] ?? '') ?></div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Last Name</label>
                                <input type="text"
                                       class="form-control<?= isset($formErrors['lastname']) ? ' is-invalid' : '' ?>"
                                       name="lastname"
                                       id="user-lastname"
                                       value="<?= htmlspecialchars($formData['lastname'] ?? '') ?>">
                                <div class="invalid-feedback" id="lastname-error"><?= htmlspecialchars($formErrors['lastname'] ?? '') ?></div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Group</label>
                                <select class="form-select<?= isset($formErrors['group_id']) ? ' is-invalid' : '' ?>" name="group_id" id="user-group">
                                    <?php foreach ($groups as $group): ?>
                                        <option value="<?= $group['lpa_user_group_ID'] ?>" <?= isset($formData['group_id']) && (int)$formData['group_id'] === (int)$group['lpa_user_group_ID'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($group['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback" id="group-error"><?= htmlspecialchars($formErrors['group_id'] ?? '') ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success"><?= $formIsEdit ? 'Update User' : 'Save User' ?></button>
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
            showToast({ message: <?= json_encode($toastMessage) ?>, type: <?= json_encode($toastType) ?> });
        });
    </script>
    <?php endif; ?>
</div>
<?php
$pageContent = ob_get_clean();
include 'includes/admin/layout.php';
