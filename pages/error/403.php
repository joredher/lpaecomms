<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$backUrl = $_SESSION['previous_page'] ?? '/home';
$forbiddenReason = $_SESSION['forbiddenReason'] ?? null;
unset($_SESSION['forbiddenReason']);
?>

<div class="container py-5">
    <div class="mb-5">
        <a href="<?= $backUrl ?>" class="pd-back-button d-flex align-items-center text-decoration-none">
            <img src="../../assets/images/icons/back.svg" alt="Back" class="me-2">
            <span>Back</span>
        </a>
    </div>
    <div class="error-box text-center bg-white rounded shadow" style="padding: 8rem 0; max-width: -webkit-fill-available">
        <h1 class="display-4">403 Forbidden</h1>
        <?php if ($forbiddenReason !== null): ?>
            <p class="mb-4 text-muted"><?= htmlspecialchars($forbiddenReason, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <p class="mb-4 text-muted">You do not have permission to view this page.</p>
        <a href="/" class="btn btn-success">Back to home page</a>
    </div>
</div>
