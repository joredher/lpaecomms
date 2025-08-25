<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../bootstrap.php';
loadRepo('repositories/UserRepository.php');
loadRepo('repositories/client/ClientRepository.php');

$conn = Database::getConnection();


$user = $_SESSION['user'] ?? [
    'firstname' => '',
    'username' => '',
    'lastname' => '',
    'email' => '',
    'lpa_fk_user_group_ID' => '',
    'lpa_users_ID' => ''
];
$clientExists = null;

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

    $clientExists = (new ClientRepository())->getAddressByClientId($user['lpa_users_ID']);
endif;

$client = array_merge($user, [
    'address' => !empty($clientExists) ? $clientExists : '',
]);

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-semibold text-primary">Edit Your Profile</h5>
</div>
<form action="/profile.store" method="POST">
    <input type="hidden" name="profile_option" value="profile">
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
        <div class="col-md-6 position-relative">
            <label class="form-label" for="autocomplete-address">Address</label>
            <input type="hidden" name="address-id" id="address-id">
            <input
                    type="text"
                        class="form-control"
                    placeholder="Type your address"
                    name="address"
                    id="autocomplete-address"
                    value="<?= ($client['address']) ?>"
                    autocomplete="off"
            >
            <ul id="suggestions" class="list-group position-absolute" style="z-index: 10;"></ul>
            <!--            <ul id="suggestions" class="list-group mt-2 position-absolute w-100 z-3"></ul>-->
        </div>


        <!--        <div class="col-md-6">-->
        <!--            <label class="form-label">Address</label>-->
        <!--            <input type="text" class="form-control" name="address"-->
        <!--                   value="--><?php //= htmlspecialchars($client['address']) ?><!--">-->
        <!--        </div>-->
    </div>
    <div class="d-flex justify-content-end gap-3 mt-4">
        <a href="../../index.php" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-success px-4">Save Changes</button>
    </div>
</form>


<hr class="my-4">
<form action="/profile.store" method="POST">
    <input type="hidden" name="profile_option" value="password">

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
        <button type="submit" class="btn btn-success px-4">Update Password</button>
    </div>
</form>

