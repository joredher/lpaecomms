<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../bootstrap.php';
loadRepo('repositories/client/ClientRepository.php');
loadRepo('repositories/BaseRepository.php');

$userId = $_SESSION['user']['id'] ?? null;
$addresses = [];
$defaultClientId = null;

if ($userId) {
    $clientRepo = new ClientRepository();
    $addresses = $clientRepo->findAllByUserId($userId);

    $baseRepo = new \repositories\BaseRepository('lpa_user_client_address_valid');
    $default = $baseRepo->findWhere('lpa_fk_users_ID', $userId);
    if ($default) {
        $defaultClientId = $default['lpa_fk_client_ID'];
    }
}

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-semibold text-primary">Your Address Book</h5>
</div>

<?php if (empty($addresses)) : ?>
    <p>You have no saved addresses.</p>
<?php else : ?>
    <form action="/profile.store" method="post">
        <input type="hidden" name="profile_option" value="address">

        <?php foreach ($addresses as $address) : ?>
            <div class="form-check mb-3">
                <input class="form-check-input" type="radio" name="client_id"
                       id="address<?= $address['lpa_clients_ID'] ?>"
                       value="<?= $address['lpa_clients_ID'] ?>"
                    <?= $address['lpa_clients_ID'] == $defaultClientId ? 'checked' : '' ?>
                >
                <label class="form-check-label" for="address<?= $address['lpa_clients_ID'] ?>">
                    <?= htmlspecialchars($address['lpa_client_address']) ?>
                </label>
            </div>
        <?php endforeach; ?>

        <div class="d-flex justify-content-end gap-3 mt-4">
            <button type="submit" class="btn btn-success px-4">Save Default</button>
        </div>
    </form>
<?php endif; ?>

