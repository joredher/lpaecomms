<?php

$conn = Database::getConnection();
$backUrl = $_SESSION['previous_page'] ?? '/home';

$user = $_SESSION['user'];

error_log('Account page loaded for user: ' . $user['firstname']);

?>
<div class="container-account">
    <div class="d-flex justify-content-between mb-5">
        <a href="<?= $backUrl ?>" class="pd-back-button d-flex align-items-center text-decoration-none">
            <img src="../../assets/images/icons/back.svg" alt="Back" class="me-2">
            <span>Back</span>
        </a>
        <span class="text-muted fw-bolder">
      Welcome! <strong class="text-white"><?= htmlspecialchars($user['firstname']) ?></strong>
    </span>
    </div>

    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 mb-4">
            <div class="bg-white rounded shadow-sm p-4">
                <h6 class="text-primary fw-semibold mb-4">Manage My Account</h6>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <a href="#profile" class="text-decoration-none text-dark fw-medium" data-target="profile">My Profile</a>
                    </li>
                    <li class="mb-2">
                        <a href="#address" class="text-decoration-none text-muted" data-target="address">Address Book</a>
                    </li>
                    <li class="mb-4">
                        <a href="#payments" class="text-decoration-none text-muted" data-target="payments">My Payment Options</a>
                    </li>
                    <h6 class="text-primary fw-semibold mt-4 mb-3">My Orders</h6>
                    <li class="mb-2">
                        <a href="#orders" class="text-decoration-none text-muted" data-target="orders">Show Orders</a>
                    </li>
                    <li class="mb-2">
                        <a href="#returns" class="text-decoration-none text-muted" data-target="returns">My Returns</a>
                    </li>
                    <li>
                        <a href="#cancellations" class="text-decoration-none text-muted" data-target="cancellations">My Cancellations</a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Profile Form -->
        <div class="col-md-9">
            <div id="dynamic-content" class="bg-white rounded shadow-sm p-4"></div>
        </div>
    </div>
</div>

<script>
    fetch('/nav.track', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            url: window.location.pathname + window.location.hash,
            prev: <?= json_encode($backUrl) ?>
        })
    }).catch(err => console.error(err));
</script>
