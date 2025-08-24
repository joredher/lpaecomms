<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../bootstrap.php';
loadRepo('repositories/client/ClientRepository.php');

$clientRepo = new ClientRepository();
$userId = $_SESSION['user']['id'] ?? null;

$addresses = [];
$primaryId = null;

if ($userId) {
    $addresses = $clientRepo->findAllByUserId($userId);
    $primaryId = $clientRepo->getPrimaryClientId($userId);
}

?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-semibold text-primary">Your Address Book</h5>
</div>

<?php if (empty($addresses)): ?>
    <p class="text-muted">No addresses saved.</p>
<?php else: ?>
<form action="/profile.store" method="POST">
    <input type="hidden" name="profile_option" value="address">
    <div class="row g-4">
        <?php foreach ($addresses as $address): ?>
            <div class="col-md-6">
                <div class="border rounded p-3 h-100">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="client_id"
                               id="addr-<?= $address['lpa_clients_ID'] ?>"
                               value="<?= $address['lpa_clients_ID'] ?>"
                            <?= ($primaryId === (int)$address['lpa_clients_ID']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="addr-<?= $address['lpa_clients_ID'] ?>">
                            <span class="d-block fw-semibold">
                                <?= htmlspecialchars($address['lpa_clients_firstname'] . ' ' . $address['lpa_clients_lastname']) ?>
                            </span>
                            <span class="d-block">
                                <?= htmlspecialchars($address['lpa_client_address']) ?>
                            </span>
                            <?php if (!empty($address['lpa_client_city']) || !empty($address['lpa_client_postcode'])): ?>
                                <span class="d-block small text-muted">
                                    <?= htmlspecialchars(trim($address['lpa_client_city'] . ' ' . $address['lpa_client_postcode'])) ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($address['lpa_client_phone'])): ?>
                                <span class="d-block small text-muted">
                                    <?= htmlspecialchars($address['lpa_client_phone']) ?>
                                </span>
                            <?php endif; ?>
                        </label>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="d-flex justify-content-end gap-3 mt-4">
        <button type="submit" class="btn btn-success px-4">Save</button>
    </div>
</form>
<?php endif; ?>
