<?php
/** Vars set by controller:
 *  $invoice, $items, $totals
 */
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$inv   = $invoice ?? [];
$its   = $items ?? [];
$cur   = $totals['currency'] ?? 'AUD';
$money = fn($n) => $cur . ' ' . number_format((float)$n, 2);
$fullName = trim(($inv['firstname'] ?? '') . ' ' . ($inv['lastname'] ?? ''));
$clientDisplayName = $inv['client_name'] ?: $fullName;
$createdFmt = $inv['created_at'] ? date('d M Y, H:i', strtotime($inv['created_at'])) : '';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$idEnc  = rawurlencode((string)($inv['id'] ?? 0));

// Build public link with ?order=
$invoiceUrl = $scheme . $host . '/checkout.confirmation?order=' . $idEnc;

// Optional: status mapping
$statusRaw = (string)($inv['status'] ?? 'A');
$statusMap = [
    'A' => ['label' => 'Active',    'pct' => 50,  'class' => 'bg-warning'],
    'P' => ['label' => 'Paid',      'pct' => 100, 'class' => 'bg-success'],
    'C' => ['label' => 'Cancelled', 'pct' => 100, 'class' => 'bg-danger'],
];
$st = $statusMap[$statusRaw] ?? ['label'=>'Active','pct'=>50,'class'=>'bg-secondary'];
?>
<style>
    @media print {
        .no-print { display: none !important; }
        .card, .table { box-shadow: none !important; }
        body { background: #fff !important; }
    }
</style>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">

            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-success rounded-pill px-3 py-2">Order Confirmed</span>
                    <h1 class="h4 m-0">Thank you for your purchase</h1>
                </div>
                <div class="no-print d-flex flex-wrap gap-2">
                    <button class="btn btn-outline-secondary btn-sm" id="btnCopyNo" type="button"
                            data-copy="<?= $h($inv['invoice_number'] ?? ('#' . ($inv['id'] ?? ''))) ?>">
                        Copy Invoice No
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" id="btnCopyLink" type="button"
                            data-copy="<?= $h($invoiceUrl) ?>">
                        Copy Link
                    </button>
                    <button class="btn btn-secondary btn-sm" type="button" onclick="window.print()">Print / Save PDF</button>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-body d-flex flex-wrap gap-3 align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small">Invoice</div>
                        <div class="fw-semibold"><?= $h($inv['invoice_number'] ?? ('#' . ($inv['id'] ?? ''))) ?></div>
                    </div>
                    <div>
                        <div class="text-muted small">Date</div>
                        <div class="fw-semibold"><?= $h($createdFmt) ?></div>
                    </div>
                    <div class="flex-grow-1" style="min-width:220px; max-width:420px;">
                        <div class="d-flex justify-content-between">
                            <div class="text-muted small">Status: <span class="fw-semibold"><?= $h($st['label']) ?></span></div>
                            <div class="text-muted small"><?= (int)$st['pct'] ?>%</div>
                        </div>
                        <div class="progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int)$st['pct'] ?>">
                            <div class="progress-bar <?= $h($st['class']) ?>" style="width: <?= (int)$st['pct'] ?>%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="text-muted small">Total</div>
                        <div class="fs-5 fw-bold"><?= $money($inv['total_amount'] ?? $totals['total'] ?? 0) ?></div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 col-lg-6">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-transparent"><strong>Customer</strong></div>
                        <div class="card-body">
                            <div class="fw-semibold"><?= $h($clientDisplayName ?: 'Customer') ?></div>
                            <?php if (!empty($inv['email'])): ?><div class="text-muted small mb-1"><?= $h($inv['email']) ?></div><?php endif; ?>
                            <?php if (!empty($inv['phone'])): ?><div class="text-muted small"><?= $h($inv['phone']) ?></div><?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                            <strong>Billing address</strong>
                            <button class="btn btn-sm btn-outline-secondary no-print" type="button" data-bs-toggle="collapse" data-bs-target="#addrCollapse" aria-expanded="true">
                                Show/Hide
                            </button>
                        </div>
                        <div class="card-body collapse show" id="addrCollapse">
                            <?= !empty($inv['client_address']) ? $h($inv['client_address']) : '<span class="text-muted small">Not provided</span>' ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm my-3">
                <div class="card-header bg-transparent d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <strong>Items</strong>
                    <div class="no-print d-flex gap-2 flex-wrap">
                        <div class="input-group input-group-sm" style="width: 260px;">
                            <span class="input-group-text">Search</span>
                            <input type="text" id="filterInput" class="form-control" placeholder="Product or SKU…">
                        </div>
                        <div class="btn-group btn-group-sm" role="group" aria-label="View mode">
                            <input type="radio" class="btn-check" name="viewMode" id="vmDetailed" autocomplete="off" checked>
                            <label class="btn btn-outline-secondary" for="vmDetailed">Detailed</label>
                            <input type="radio" class="btn-check" name="viewMode" id="vmCompact" autocomplete="off">
                            <label class="btn btn-outline-secondary" for="vmCompact">Compact</label>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle m-0" id="itemsTable">
                            <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th class="text-nowrap">SKU</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end detailed-col">Unit</th>
                                <th class="text-end">Total</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (!empty($its)): ?>
                                <?php foreach ($its as $i => $it): ?>
                                    <?php
                                    $name = $h($it['name'] ?? 'Item');
                                    $sku  = $h($it['sku'] ?? '');
                                    $qty  = (int)($it['quantity'] ?? 0);
                                    $unit = $money($it['unit_price'] ?? 0);
                                    $tot  = $money($it['total_price'] ?? 0);
                                    ?>
                                    <tr data-name="<?= strtolower($name) ?>" data-sku="<?= strtolower($sku) ?>">
                                        <td>
                                            <div class="fw-semibold"><?= $name ?></div>
                                            <div class="text-muted small detailed-col">Line #<?= $i + 1 ?></div>
                                        </td>
                                        <td class="text-muted"> <?= $sku ?></td>
                                        <td class="text-end"><?= $qty ?></td>
                                        <td class="text-end detailed-col"><?= $unit ?></td>
                                        <td class="text-end fw-semibold"><?= $tot ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">No items</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 col-lg-6">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-transparent"><strong>Payment</strong></div>
                        <div class="card-body">
                            <div class="small text-muted mb-1">Method</div>
                            <div class="fw-semibold">Credit / Debit Card</div>
                            <div class="small text-muted mt-2">Transaction ID</div>
                            <div class="fw-semibold"><?= $h($inv['txn_id'] ?? '—') ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-transparent"><strong>Totals</strong></div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Total</span>
                                <span class="fs-5 fw-bold"><?= $money($inv['total_amount'] ?? $totals['total'] ?? 0) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 justify-content-end my-4 no-print">
                <a class="btn btn-outline-secondary" href="/shop">Continue shopping</a>
                <a class="btn btn-primary" href="/orders">View my orders</a>
            </div>

        </div>
    </div>
</div>

<script>
    // copy helpers
    function copyText(text) {
        if (!text) return;
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text);
        } else {
            const ta = document.createElement('textarea');
            ta.value = text; document.body.appendChild(ta);
            ta.select(); document.execCommand('copy'); document.body.removeChild(ta);
        }
    }
    document.getElementById('btnCopyNo')?.addEventListener('click', (e) => {
        copyText(e.currentTarget.dataset.copy);
        e.currentTarget.textContent = 'Copied!';
        setTimeout(() => e.currentTarget.textContent = 'Copy Invoice No', 1200);
    });
    document.getElementById('btnCopyLink')?.addEventListener('click', (e) => {
        copyText(e.currentTarget.dataset.copy);
        e.currentTarget.textContent = 'Link Copied!';
        setTimeout(() => e.currentTarget.textContent = 'Copy Link', 1200);
    });

    // search + compact view
    const filterInput = document.getElementById('filterInput');
    const rows = Array.from(document.querySelectorAll('#itemsTable tbody tr'));
    filterInput?.addEventListener('input', () => {
        const q = filterInput.value.trim().toLowerCase();
        rows.forEach(tr => {
            const name = tr.dataset.name || '';
            const sku  = tr.dataset.sku || '';
            tr.style.display = (name.includes(q) || sku.includes(q)) ? '' : 'none';
        });
    });
    document.getElementById('vmDetailed')?.addEventListener('change', e => {
        if (!e.currentTarget.checked) return;
        document.querySelectorAll('.detailed-col').forEach(el => el.classList.remove('d-none'));
    });
    document.getElementById('vmCompact')?.addEventListener('change', e => {
        if (!e.currentTarget.checked) return;
        document.querySelectorAll('.detailed-col').forEach(el => el.classList.add('d-none'));
    });
</script>
