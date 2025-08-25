<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$backUrl = $_SESSION['previous_page'] ?? '/profile';
// expected: $invoice, $items, $totals
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$inv = $invoice ?? [];
$its = $items ?? [];
$cur = $totals['currency'] ?? 'AUD';
$money = fn($n) => $cur . ' ' . number_format((float)$n, 2);
$createdFmt = !empty($inv['created_at']) ? date('d M Y, H:i', strtotime($inv['created_at'])) : '';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = 'localhost';
$idEnc = rawurlencode((string)($inv['id'] ?? 0));
$publicUrl = $scheme . $host . '/checkout.confirmation?order=' . $idEnc;

// Status mapping (A|P|C)
$statusRaw = (string)($inv['status'] ?? 'A');
$statusMap = [
    'A' => ['label' => 'Processing', 'pct' => 50],
    'P' => ['label' => 'Paid', 'pct' => 100],
    'C' => ['label' => 'Cancelled', 'pct' => 100],
];
$st = $statusMap[$statusRaw] ?? ['label' => 'Processing', 'pct' => 50];
?>

<style>

    /* White card + table look */
    .lpa-card {
        background: #fff;
    }

    .lpa-table thead th {
        background: #f8f9fa;
        border: 0;
        letter-spacing: .03em;
    }

    .lpa-table tbody td {
        border-top: 1px solid rgba(0, 0, 0, .06);
    }

    /* Sub-panels */
    .lpa-subcard {
        background: #fff;
        border: 1px solid rgba(0, 0, 0, .05);
        box-shadow: 0 1px 6px rgba(0, 0, 0, .05);
    }

    /* Totals box (cart-like) */
    .lpa-totalbox {
        background: #f6faf8;
        border: 1px solid rgba(0, 0, 0, .07);
        box-shadow: 0 2px 10px rgba(0, 0, 0, .06);
    }

    /* CTA green button */
    .lpa-btn-green {
        background: #59c27d;
        color: #fff;
        border: 0;
        border-radius: .5rem;
    }

    .lpa-btn-green:hover {
        background: #4bae6e;
        color: #fff;
    }

    /* Back button on gradient */
    .lpa-back-btn {
        border-color: rgba(255, 255, 255, .5) !important;
        color: #fff;
    }

    .lpa-back-btn:hover {
        background: rgba(255, 255, 255, .1);
        color: #fff;
    }

    /* Thumbnails */
    .lpa-thumb {
        width: 50px;
        height: 50px;
        overflow: hidden;
        background: #f1f3f5;
        box-shadow: 0 1px 4px rgba(0, 0, 0, .08);
    }

    .lpa-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Responsive title size */
    .h4-md {
        font-size: 1.5rem;
    }

    @media (min-width: 768px) {
        .h4-md {
            font-size: 1.75rem;
        }
    }

    /* Status card & progress */
    .lpa-status {
        background: #fff;
    }

    .lpa-progress {
        height: 10px;
        background: rgba(0, 0, 0, .06);
        border-radius: 999px;
        overflow: hidden;
    }

    .lpa-progress-bar {
        background: #59c27d; /* same green */
        transition: width .7s ease;
    }

    @media print {
        body * {
            visibility: hidden;      /* oculta todo */
        }
        #printArea, #printArea * {
            visibility: visible;     /* muestra solo el área */
        }
        #printArea {
            position: absolute;
            left: 0; top: 0; right: 0;
            width: 100%;
        }
    }

</style>
<div class="lpa-gradient-bg py-4 py-md-5">
    <div class="container">

        <!-- Top bar -->
        <div class="d-flex align-items-center justify-content-between mb-3 mb-md-4">
            <a href="<?= $backUrl ?>" class="btn btn-outline-light btn-sm lpa-back-btn"><span class="me-1">←</span> Back</a>
            <h1 class="h5 h4-md text-white m-0">Order Confirmation</h1>
            <div class="d-flex align-items-center gap-2">
                <button id="btnCopyNo" class="btn btn-outline-light btn-sm" type="button"
                        data-copy="<?= $h($inv['invoice_number'] ?? ('#' . $inv['id'])) ?>">Copy No
                </button>
                <button id="btnCopyLink" class="btn btn-outline-light btn-sm" disabled type="button"
                        data-copy="<?= $h($publicUrl) ?>">Copy Link
                </button>
                <button class="btn btn-light btn-sm" type="button" onclick="window.print()">Print</button>
            </div>
        </div>

        <!-- Status tracker -->
        <div class="lpa-status card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="text-muted small text-uppercase">Order Status</div>
                    <div class="small fw-semibold"><?= $h($st['label']) ?></div>
                </div>
                <div class="progress lpa-progress" role="progressbar"
                     aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int)$st['pct'] ?>">
                    <div class="progress-bar lpa-progress-bar" style="width: 0%"></div>
                </div>
                <div class="d-flex justify-content-between mt-2 small text-muted">
                    <span>Placed</span><span>Processing</span><span>Paid</span>
                </div>
            </div>
        </div>

        <!-- Main card -->
        <div class="card lpa-card shadow-sm border-0 rounded-3 mb-4" id="printArea">
            <div class="card-body p-3 p-md-4">
                <div class="row g-3 g-lg-4">

                    <!-- ITEMS -->
                    <div class="col-12 col-lg-8">

                        <!-- Items header tools -->
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                            <div class="text-white-50 small">
                                <span class="me-3 text-black-50">No: <span
                                            class="text-dark fw-semibold"><?= $h($inv['invoice_number'] ?? ('#' . $inv['id'])) ?></span></span>
                                <span class="text-black-50">Date: <span class="text-dark fw-semibold"><?= $h($createdFmt) ?></span></span>
                            </div>
                            <div class="d-flex gap-2">
                                <div class="input-group input-group-sm" style="width:260px;">
                                    <span class="input-group-text">Search</span>
                                    <input id="filterInput" type="text" class="form-control"
                                           placeholder="Product or SKU…">
                                </div>
                                <div class="btn-group btn-group-sm" role="group" aria-label="View mode">
                                    <input type="radio" class="btn-check" name="viewMode" id="vmDetailed"
                                           autocomplete="off" checked>
                                    <label class="btn btn-outline-secondary" for="vmDetailed">Detailed</label>
                                    <input type="radio" class="btn-check" name="viewMode" id="vmCompact"
                                           autocomplete="off">
                                    <label class="btn btn-outline-secondary" for="vmCompact">Compact</label>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table align-middle lpa-table m-0" id="itemsTable">
                                <thead>
                                <tr>
                                    <th class="text-uppercase small text-muted ps-0">Product</th>
                                    <th class="text-uppercase small text-muted text-end">Qty</th>
                                    <th class="text-uppercase small text-muted text-end d-none d-sm-table-cell detailed-col">
                                        Unit
                                    </th>
                                    <th class="text-uppercase small text-muted text-end pe-0">Total</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($its as $i => $it): ?>
                                    <?php
                                    $name = $h($it['name'] ?? 'Item');
                                    $sku = $h($it['sku'] ?? '');
                                    $image = $h('assets/images/test-images/' . ($it['image'] ?: 'hdd1.jpg'));
                                    $qty = (int)($it['quantity'] ?? 0);
                                    $unit = $money($it['unit_price'] ?? 0);
                                    $tot = $money($it['total_price'] ?? 0);
                                    ?>
                                    <tr data-name="<?= strtolower($name) ?>" data-sku="<?= strtolower($sku) ?>">
                                        <td class="ps-0">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="lpa-thumb rounded">
                                                    <img src="<?= $image ?>"
                                                         onerror="this.src='/assets/images/placeholder.png'"
                                                         alt="<?= $name ?>">
                                                </div>
                                                <div>
                                                    <div class="fw-semibold"><?= $name ?></div>
                                                    <div class="text-muted small detailed-col">
                                                        SKU: <?= $sku ?: '—' ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end"><?= $qty ?></td>
                                        <td class="text-end d-none d-sm-table-cell detailed-col"><?= $unit ?></td>
                                        <td class="text-end fw-semibold pe-0"><?= $tot ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Billing block -->
                        <div class="lpa-subcard rounded-3 p-3 p-md-4 mt-3">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <div class="text-uppercase small text-muted mb-1">Billing name</div>
                                    <div class="fw-semibold">
                                        <?= $h($inv['client_name'] ?? trim(($inv['firstname'] ?? '') . ' ' . ($inv['lastname'] ?? ''))) ?>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="text-uppercase small text-muted mb-1">Contact</div>
                                    <div class="small">
                                        <?= $h($inv['client_email'] ?? '—') ?><?= !empty($inv['client_phone']) ? ' • ' . $h($inv['client_phone']) : '' ?>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="text-uppercase small text-muted mb-1">Billing address</div>
                                    <div class="small"><?= $h($inv['client_address'] ?? 'Not provided') ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TOTALS -->
                    <div class="col-12 col-lg-4">
                        <div class="lpa-totalbox rounded-3 p-3 p-md-4 ms-lg-auto">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Subtotal</span>
                                <span><?= $money($totals['total'] ?? 0) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Shipping</span>
                                <span>AUD 0.00</span>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="fw-semibold">Total</span>
                                <span class="fs-5 fw-bold"><?= $money($totals['total'] ?? 0) ?></span>
                            </div>
                            <a href="/products" class="btn lpa-btn-green w-100 mb-2">Continue shopping</a>
                            <a href="/profile#orders" class="btn btn-outline-success w-100">View my orders</a>

                            <div class="lpa-mini mt-3">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted small">Order No</span>
                                    <span class="small fw-semibold"><?= $h($inv['invoice_number'] ?? ('#' . $inv['id'])) ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted small">Date</span>
                                    <span class="small"><?= $h($createdFmt) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div><!-- /row -->
            </div>
        </div>
    </div>
</div>

<script>
    // Copy helpers
    function copyText(text) {
        if (!text) return;
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text);
        } else {
            const ta = document.createElement('textarea');
            ta.value = text;
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
        }
    }

    document.getElementById('btnCopyNo')?.addEventListener('click', e => {
        copyText(e.currentTarget.dataset.copy);
        e.currentTarget.textContent = 'Copied!';
        setTimeout(() => e.currentTarget.textContent = 'Copy No', 1200);
    });
    document.getElementById('btnCopyLink')?.addEventListener('click', e => {
        copyText(e.currentTarget.dataset.copy);
        e.currentTarget.textContent = 'Link Copied!';
        setTimeout(() => e.currentTarget.textContent = 'Copy Link', 1200);
    });

    // Search filter
    const filterInput = document.getElementById('filterInput');
    const rows = [...document.querySelectorAll('#itemsTable tbody tr')];
    filterInput?.addEventListener('input', () => {
        const q = filterInput.value.trim().toLowerCase();
        rows.forEach(tr => {
            const name = (tr.dataset.name || '');
            const sku = (tr.dataset.sku || '');
            tr.style.display = (name.includes(q) || sku.includes(q)) ? '' : 'none';
        });
    });

    // Compact / Detailed toggle
    const showDetailed = (on) => document.querySelectorAll('.detailed-col')
        .forEach(el => el.classList.toggle('d-none', !on));
    document.getElementById('vmDetailed')?.addEventListener('change', e => {
        if (e.currentTarget.checked) showDetailed(true);
    });
    document.getElementById('vmCompact')?.addEventListener('change', e => {
        if (e.currentTarget.checked) showDetailed(false);
    });

    // Status bar animation
    (function () {
        const bar = document.querySelector('.lpa-progress-bar');
        const wrap = document.querySelector('.lpa-progress');
        if (!bar || !wrap) return;
        const target = parseInt(wrap.getAttribute('aria-valuenow') || '0', 10);
        requestAnimationFrame(() => {
            bar.style.width = target + '%';
        });
    })();

    fetch('/nav.track', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            url: window.location.pathname + window.location.hash,
            prev: <?= json_encode($backUrl) ?>
        })
    }).catch(err => console.error(err));
</script>

<style>
    @media print {
        .lpa-back-btn, #btnCopyNo, #btnCopyLink, .input-group, .btn-group {
            display: none !important;
        }
    }
</style>
