<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$backUrl = $_SESSION['previous_page'] ?? 'home';
$notFoundReason = $_SESSION['notFoundReason'] ?: null;

unset($_SESSION['notFoundReason']);
?>

<div class="container py-5">
    <div class="mb-5">
        <a href="<?= $backUrl ?>" class="pd-back-button d-flex align-items-center text-decoration-none">
            <img src="assets/images/icons/back.svg" alt="Back" class="me-2">
            <span>Back</span>
        </a>
    </div>
    <div class="error-box text-center bg-white rounded shadow" style="padding: 8rem 0; max-width: -webkit-fill-available">
        <h1 class="display-4">404 Not Found</h1>
        <?php if ($notFoundReason !== null): ?>
            <p class="mb-4 text-muted"><?= $notFoundReason ?></p>
        <?php endif; ?>
        <p class="mb-4 text-muted">Your visited page not found. You may go home page.</p>
        <a href="home" class="btn btn-success">Back to home page</a>
    </div>
</div>