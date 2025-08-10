<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$backUrl = $_SESSION['previous_page'];
?>

<div class="container py-5">
    <div class="mb-5">
        <a href="<?= $backUrl ?>" class="pd-back-button d-flex align-items-center text-decoration-none">
            <img src="../../assets/images/icons/back.svg" alt="Back" class="me-2">
            <span>Back</span>
        </a>
    </div>
    <div class="error-box text-center bg-white rounded shadow" style="padding: 8rem 0">
        <h1 class="display-4">404 Not Found</h1>
        <p class="mb-4 text-muted">Your visited page not found. You may go home page.</p>
        <a href="/" class="btn btn-success">Back to home page</a>
    </div>
</div>