<?php
require_once 'repositories/UserRepository.php';


$user = $_SESSION['user'];

if (isset($_SESSION['user'])) :
    $repository = new UserRepository();
//    $user = $repository->findWhere('lpa_users_ID', $user['id']);
    $user = $repository->getColumnsAsById($user['id'], [
        'lpa_user_username AS username',
        'lpa_user_firstname AS firstname',
        'lpa_user_lastname AS lastname',
        'lpa_user_email AS email',
        'lpa_fk_user_group_ID',
        'lpa_users_ID',
    ], 'lpa_users_ID');
endif;


$client = array_merge($user, [
    'address' => ''
]);

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-semibold text-primary">Edit Your Profile</h5>
</div>
<form action="?route=profile.store" method="POST">
    <div class="row g-4">
        <div class="col-md-6">
            <label class="form-label">First Name</label>
            <input type="text" class="form-control" name="firstname"
                   value="<?= htmlspecialchars($client['firstname']) ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">Last Name</label>
            <input type="text" class="form-control" name="lastname"
                   value="<?= htmlspecialchars($client['lastname']) ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email"
                   value="<?= htmlspecialchars($client['email']) ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">Address</label>
            <input type="text" class="form-control" name="address"
                   value="<?= htmlspecialchars($client['address']) ?>">
        </div>
    </div>

    <hr class="my-4">
    <h6 class="fw-semibold mb-3">Password Changes</h6>

    <div class="row g-4">
        <div class="col-md-4">
            <input type="password" class="form-control" name="current_password"
                   placeholder="Current Password">
        </div>
        <div class="col-md-4">
            <input type="password" class="form-control" name="new_password" placeholder="New Password">
        </div>
        <div class="col-md-4">
            <input type="password" class="form-control" name="confirm_password"
                   placeholder="Confirm New Password">
        </div>
    </div>

    <div class="d-flex justify-content-end gap-3 mt-4">
        <a href="../../index.php" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-success px-4">Save Changes</button>
    </div>
</form>
