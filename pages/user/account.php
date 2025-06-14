<?php

$conn = Database::getConnection();
$backUrl = $_SESSION['previous_page'];

$user = $_SESSION['user'];


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
                        <a href="#" class="text-decoration-none text-dark fw-medium" data-target="profile">My Profile</a>
                    </li>
                    <li class="mb-2">
                        <a href="#" class="text-decoration-none text-muted" data-target="address">Address Book</a>
                    </li>
                    <li class="mb-4">
                        <a href="#" class="text-decoration-none text-muted" data-target="payments">My Payment Options</a>
                    </li>
                    <h6 class="text-primary fw-semibold mt-4 mb-3">My Orders</h6>
                    <li class="mb-2">
                        <a href="#" class="text-decoration-none text-muted" data-target="returns">My Returns</a>
                    </li>
                    <li>
                        <a href="#" class="text-decoration-none text-muted" data-target="cancellations">My Cancellations</a>
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
<!--<div class="container p-4 mb-5">-->
<!--    <div class="d-flex justify-content-between">-->
<!--        <div class="mb-5">-->
<!--            <a href="--><?php //= $backUrl ?><!--" class="pd-back-button d-flex align-items-center text-decoration-none">-->
<!--                <img src="../../assets/images/icons/back.svg" alt="Back" class="me-2">-->
<!--                <span>Back</span>-->
<!--            </a>-->
<!---->
<!--        </div>-->
<!--        <div class="mb-5">-->
<!--        <span class="text-muted">-->
<!--            Welcome! <strong>--><?php //= htmlspecialchars($user['firstname']) ?><!--</strong>-->
<!--        </span>-->
<!--        </div>-->
<!--    </div>-->
<!--    <div class="row">-->
<!---->
<!--        <!-- Sidebar -->-->
<!--        <div class="col-md-3 mb-4">-->
<!--            <div class="bg-white rounded shadow-sm p-4">-->
<!--                <h6 class="text-primary fw-semibold mb-4">Manage My Account</h6>-->
<!--                <ul class="list-unstyled">-->
<!--                    <li class="mb-2"><a href="?route=profile.create" class="text-decoration-none text-dark fw-medium">My-->
<!--                            Profile</a></li>-->
<!--                    <li class="mb-2"><a href="#" class="text-decoration-none text-muted">Address Book</a></li>-->
<!--                    <li class="mb-4"><a href="#" class="text-decoration-none text-muted">My Payment Options</a></li>-->
<!--                    <h6 class="text-primary fw-semibold mb-3">My Orders</h6>-->
<!--                    <li class="mb-2"><a href="#" class="text-decoration-none text-muted">My Returns</a></li>-->
<!--                    <li><a href="#" class="text-decoration-none text-muted">My Cancellations</a></li>-->
<!--                </ul>-->
<!--            </div>-->
<!--        </div>-->
<!---->
<!--        <!-- Profile Form -->-->
<!--        <div class="col-md-9">-->
<!--            <div class="bg-white rounded shadow-sm p-4">-->
<!--                <div class="d-flex justify-content-between align-items-center mb-4">-->
<!--                    <h5 class="fw-semibold text-primary">Edit Your Profile</h5>-->
<!---->
<!--                </div>-->
<!---->
<!--                <form action="?route=profile.store" method="POST">-->
<!--                    <div class="row g-4">-->
<!--                        <div class="col-md-6">-->
<!--                            <label class="form-label">First Name</label>-->
<!--                            <input type="text" class="form-control" name="firstname"-->
<!--                                   value="--><?php //= htmlspecialchars($client['lpa_clients_firstname']) ?><!--">-->
<!--                        </div>-->
<!--                        <div class="col-md-6">-->
<!--                            <label class="form-label">Last Name</label>-->
<!--                            <input type="text" class="form-control" name="lastname"-->
<!--                                   value="--><?php //= htmlspecialchars($client['lpa_clients_lastname']) ?><!--">-->
<!--                        </div>-->
<!---->
<!--                        <div class="col-md-6">-->
<!--                            <label class="form-label">Email</label>-->
<!--                            <input type="email" class="form-control" name="email"-->
<!--                                   value="--><?php //= htmlspecialchars($client['lpa_client_email']) ?><!--">-->
<!--                        </div>-->
<!--                        <div class="col-md-6">-->
<!--                            <label class="form-label">Address</label>-->
<!--                            <input type="text" class="form-control" name="address"-->
<!--                                   value="--><?php //= htmlspecialchars($client['lpa_client_address']) ?><!--">-->
<!--                        </div>-->
<!--                    </div>-->
<!---->
<!--                    <hr class="my-4">-->
<!--                    <h6 class="fw-semibold mb-3">Password Changes</h6>-->
<!---->
<!--                    <div class="row g-4">-->
<!--                        <div class="col-md-4">-->
<!--                            <input type="password" class="form-control" name="current_password"-->
<!--                                   placeholder="Current Password">-->
<!--                        </div>-->
<!--                        <div class="col-md-4">-->
<!--                            <input type="password" class="form-control" name="new_password" placeholder="New Password">-->
<!--                        </div>-->
<!--                        <div class="col-md-4">-->
<!--                            <input type="password" class="form-control" name="confirm_password"-->
<!--                                   placeholder="Confirm New Password">-->
<!--                        </div>-->
<!--                    </div>-->
<!---->
<!--                    <div class="d-flex justify-content-end gap-3 mt-4">-->
<!--                        <a href="?route=home" class="btn btn-outline-secondary">Cancel</a>-->
<!--                        <button type="submit" class="btn btn-success px-4">Save Changes</button>-->
<!--                    </div>-->
<!--                </form>-->
<!---->
<!--            </div>-->
<!--        </div>-->
<!---->
<!--    </div>-->
<!---->
<!--</div>-->
