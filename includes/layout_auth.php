<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $title ?? 'Account' ?></title>
    <link rel="icon" href="../assets/images/Logo.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="./../assets/css/authentication.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
          rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;800&display=swap" rel="stylesheet">
</head>
<body>

<div class="login_layout">
    <?php if (isset($pageContent)): ?>
        <?php include $pageContent ?>
    <?php endif; ?>
</div>

<div class="toast-container position-fixed top-0 end-0 p-3 z-3">
    <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="4000">
        <div class="toast-header">
            <img src="" class="rounded me-2" id="toast-img" alt="toast-icon" style="width: 20px; height: 20px;">
            <strong class="me-auto" id="toast-title">Notification</strong>
            <small class="text-muted" id="toast-time">Just now</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body" id="toast-body">
            Message goes here.
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
<!--<script type="application/javascript" src="../assets/js/ecommerce_script.js"></script>-->
<script type="application/javascript" src="../assets/js/toast.js"></script>
<?php if (isset($_SESSION['flash_message'])): $msg = $_SESSION['flash_message']; ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            showToast({
                title: 'Notice',
                message: <?= json_encode($msg['message']) ?>,
                type: <?= json_encode($msg['type']) ?>,
                image: <?= isset($msg['img']) ? json_encode($msg['img']) : 'assets/images/icons/success.png' ?>
            });
        });
    </script>
    <?php unset($_SESSION['flash_message']); ?>
<?php endif; ?>

</body>
</html>