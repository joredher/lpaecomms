<?php
$title = $title ?? 'LPA Ecommerce';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?></title>
    <meta name="theme-color" content="#0d6efd">
    <?php if (!empty($enablePWA)): ?>
        <link rel="manifest" href="/manifest.webmanifest">
    <?php endif; ?>

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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
          rel="stylesheet">
</head>
<body>
<div id="main">
    <?php include 'includes/header.php' ?>
    <?php include 'includes/brands.php' ?>
    <div class="content">
        <?php if (isset($pageContent)): ?>
            <?php include $pageContent ?>
        <?php else: ?>
            <?php include 'pages/error/404.php' ?>
        <?php endif; ?>
    </div>
    <?php include 'includes/footer.php' ?>
</div>
<!-- Accessibility Preferences Modal -->
<div class="modal fade lpa-modal" id="accessibilityModal" tabindex="-1" aria-labelledby="accessibilityTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="accessibilityTitle">Accessibility Preferences</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="a11y-form">
                    <div class="mb-3">
                        <label for="a11y-text-size" class="form-label">Text size</label>
                        <div class="d-flex align-items-center gap-3">
                            <select id="a11y-text-size" class="form-select w-auto" aria-describedby="textSizeHelp">
                                <option value="normal">Normal</option>
                                <option value="large">Large</option>
                                <option value="xlarge">Extra large</option>
                            </select>
                            <div class="a11y-preview" aria-hidden="true" title="Preview">
                                <span>A</span><span>A</span><span>A</span>
                            </div>
                        </div>
                        <div id="textSizeHelp" class="form-text">Live preview shows how text size affects content.</div>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="a11y-contrast">
                        <label class="form-check-label" for="a11y-contrast">High contrast</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="a11y-dark-mode">
                        <label class="form-check-label" for="a11y-dark-mode">Dark mode</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="a11y-underline-links">
                        <label class="form-check-label" for="a11y-underline-links">Underline links</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="a11y-reduce-motion">
                        <label class="form-check-label" for="a11y-reduce-motion">Reduce motion</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="a11y-focus-outline">
                        <label class="form-check-label" for="a11y-focus-outline">Highlight keyboard focus</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" id="a11y-reset">Reset</button>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal" id="a11y-save">Apply</button>
            </div>
        </div>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
<script type="application/javascript" src="../assets/js/address_autocomplete.js"></script>
<script type="application/javascript" src="../assets/js/ecommerce_script.js"></script>
<script type="application/javascript" src="../assets/js/toast.js"></script>
<script type="application/javascript" src="../assets/js/search.js"></script>
<script type="application/javascript">
    // Ensure accessibility preferences apply as early as possible on layout load
    (function(){
        try {
            const prefs = JSON.parse(localStorage.getItem('a11y')||'{}');
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

<?php if (!empty($enablePWA)): ?>
    <script>
      if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
          navigator.serviceWorker.register('/sw.js').catch(function (err) {
            console.error('SW registration failed:', err);
          });
        });
      }
    </script>
<?php endif; ?>

<?php if (isset($_SESSION['flash_message'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            showToast({
                title: 'Cart',
                message: <?= json_encode($_SESSION['flash_message']['message']) ?>,
                type: <?= json_encode($_SESSION['flash_message']['type']) ?>,
                image: 'assets/images/icons/success.png'
            });
        });
    </script>
    <?php unset($_SESSION['flash_message']); ?>
<?php endif; ?>

</body>
</html>
