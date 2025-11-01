<?php
$title = $title ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?></title>
    <meta name="theme-color" content="#0d6efd">

    <link rel="manifest" href="/manifest.webmanifest">

    <link rel="icon" type="image/svg+xml" href="../assets/images/Logo.svg">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="../assets/css/ecomms_style_v1.css">
    <link rel="stylesheet" href="../assets/css/bootstrap_customize.css">
    <link rel="stylesheet" href="../assets/css/product_detail.css">
    <link rel="stylesheet" href="../assets/css/normalize.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
</head>
<body>
<div id="main">
    <?php include __DIR__ . '/header.php'; ?>
    <div class="content">
        <?php if (isset($pageContent)): ?>
            <?php if (is_string($pageContent) && is_file($pageContent)): ?>
                <?php include $pageContent; ?>
            <?php else: ?>
                <?= $pageContent ?>
            <?php endif; ?>
        <?php else: ?>
            <?php include __DIR__ . '/../../pages/error/404.php'; ?>
        <?php endif; ?>
    </div>
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
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmTitle">Confirm Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="confirmMessage">Are you sure?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmOk">OK</button>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script type="application/javascript" src="../assets/js/address_autocomplete.js"></script>
<script type="application/javascript" src="../assets/js/ecommerce_script.js"></script>
<script type="application/javascript" src="../assets/js/toast.js"></script>
<script type="application/javascript" src="../assets/js/search.js"></script>
<script>
  window.CSRF_TOKEN = '<?= e(csrf_token()) ?>';
  window.IS_AUTHENTICATED = <?= isset($_SESSION['user']['id']) ? 'true' : 'false' ?>;
  window.SERVER_A11Y = <?= json_encode($_SESSION['a11y_prefs'] ?? null) ?>;
  (function(){
    try {
      let prefs = {};
      try { prefs = JSON.parse(localStorage.getItem('a11y')||'{}') || {}; } catch { prefs = {}; }
      if (window.SERVER_A11Y && typeof window.SERVER_A11Y === 'object') {
        prefs = Object.assign({}, prefs, window.SERVER_A11Y);
        localStorage.setItem('a11y', JSON.stringify(prefs));
      }
      const b = document.body;
      if (prefs.textSize === 'large') b.classList.add('a11y-text-lg');
      if (prefs.textSize === 'xlarge') b.classList.add('a11y-text-xl');
      if (prefs.contrast) b.classList.add('a11y-contrast');
      if (prefs.underlineLinks) b.classList.add('a11y-underline-links');
      if (prefs.reduceMotion) b.classList.add('a11y-reduce-motion');
      if (prefs.focusOutline) b.classList.add('a11y-focus-outline');
    } catch(e) { /* noop */ }
  })();
  </script>
<?php if (!empty($adminJs)): ?>
<script type="application/javascript" src="<?= htmlspecialchars($adminJs) ?>"></script>
<?php endif; ?>
<script>
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register('/sw.js').catch(function (err) {
        console.error('SW registration failed:', err);
      });
    });
  }
</script>
</body>
</html>
