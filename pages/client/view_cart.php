<?php

require_once 'includes/config.php';
//require_once 'includes/pagination.php';
$conn = Database::getConnection();
$backUrl = $_SESSION['previous_page'] ?? '/home';
$cart = $_SESSION['cart'] ?? [];
$total = $_SESSION['total'] ?? 0;

?>

<div class="pd-cart-section container py-5">
    <div class="mb-5">
        <a href="<?= $backUrl ?>" class="pd-back-button d-flex align-items-center text-decoration-none">
            <img src="../../assets/images/icons/back.svg" alt="Back" class="me-2">
            <span>Back</span>
        </a>
    </div>
    <div class="mt-5">
        <?php if (!$cart) : ?>
            <div class="container text-center align-content-center mb-4">
                <h3 class="text-white">Opps!</h3>
                <h5 class="text-white">Your cart is empty.</h5>
            </div>
        <?php else : ?>
            <form action="?route=cart.update" method="POST">
                <div class="table-responsive">
                    <table class="table align-middle table-striped">
                        <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th>Remove</th>
                        </tr>
                        </thead>
                        <tbody class="table-group-divider">
                        <?php foreach ($cart as $id => $item):
                            $subtotal = $item['price'] * $item['quantity'];
                            $total += $subtotal;
                            ?>
                            <tr>
                                <td class="text-start" data-label="Product">
                                    <div class="d-flex align-items-center">
                                        <img src="<?= htmlspecialchars($item['image']) ?>"
                                             alt="<?= htmlspecialchars($item['name']) ?>"
                                             style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px;"
                                             class="me-3"
                                             onerror="this.src='<?= PRODUCT_PLACEHOLDER_URL ?>';">
                                        <span><?= htmlspecialchars($item['name']) ?></span>
                                    </div>
                                </td>
                                <td class="cart-price" data-label="Price" data-price-aud="<?= number_format((float)$item['price'], 2, '.', '') ?>">$<?= number_format((float)$item['price'], 2) ?> AUD</td>
                                <td data-label="Quantity">
                                    <input type="hidden" name="id[]" value="<?= $id ?>">
                                    <input type="number"
                                           name="quantity[]"
                                           class="form-control text-center cart-qty"
                                           min="1"
                                           max="<?= $item['stock'] ?? 10 ?>"
                                           value="<?= $item['quantity'] ?>"
                                           style="width: 70px;">
                                    <div class="invalid-feedback"></div>
                                </td>
                                <td class="cart-subtotal" data-label="Subtotal" data-price-aud="<?= number_format((float)$subtotal, 2, '.', '') ?>">$<?= number_format((float)$subtotal, 2) ?> AUD</td>
                                <td data-label="Remove">
                                    <a href="?route=cart.remove&id=<?= $id ?>" class="btn btn-sm btn-outline-danger" aria-label="Remove item">✖</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mt-1 mb-5">
                    <a href="?route=products" class="btn btn-return-shop">Return To Shop</a>
                    <button type="submit" class="btn btn-update-cart" id="updateCartBtn">Update Cart</button>
                </div>
            </form>

            <!-- Cart Actions (Coupon + Summary) -->
            <div class="row mt-4">
                <!-- Coupon Section -->
                <div class="col-md-6 mb-3">
                    <?php if (isset($_SESSION['user'])): ?>
                        <form class="coupon-form d-flex flex-column flex-md-row align-items-md-center" onsubmit="return false;">
                            <label class="w-100 w-md-auto">
                                <input type="text" name="coupon_code" class="form-control me-md-2 mb-2 mb-md-0" placeholder="Coupon Code" disabled aria-disabled="true">
                            </label>
                            <button type="button" class="btn btn-success" disabled aria-disabled="true">Apply Coupon</button>
                        </form>
                        <small class="text-muted d-block mt-2">Coupons are temporarily disabled.</small>
                    <?php endif; ?>
                </div>

                <!-- Cart Total Summary -->
                <div class="col-md-6">
                    <div class="card border shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Cart Total</h5>
                            <div class="d-flex justify-content-between">
                                <span>Subtotal:</span>
                                <strong data-price-aud="<?= number_format((float)$total, 2, '.', '') ?>">$<?= number_format((float)$total, 2) ?> AUD</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Shipping:</span>
                                <strong>Free</strong>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Total:</span>
                                <strong data-price-aud="<?= number_format((float)$total, 2, '.', '') ?>">$<?= number_format((float)$total, 2) ?> AUD</strong>
                            </div>
                            <a href="/checkout" class="btn btn-success w-100" id="checkoutBtn">Proceed to Checkout</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Confirm Remove Modal -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Remove Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to remove this item from your cart?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmRemoveBtn">Remove</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const qtyInputs = document.querySelectorAll('.cart-qty');
        const updateBtn = document.getElementById('updateCartBtn');
        const checkoutBtn = document.getElementById('checkoutBtn');
        const modalEl = document.getElementById('confirmDeleteModal');
        const confirmBtn = document.getElementById('confirmRemoveBtn');
        let pendingRemoveUrl = null;

        const preventCheckout = (e) => {
            if (checkoutBtn.classList.contains('disabled')) {
                e.preventDefault();
            }
        };

        const validate = () => {
            let hasError = false;

            qtyInputs.forEach(input => {
                const value = parseInt(input.value, 10);
                const max = parseInt(input.max, 10);
                const feedback = input.nextElementSibling;
                let message = '';

                if (!input.value) {
                    message = 'Quantity required';
                } else if (value < 1) {
                    message = 'Quantity must be at least 1';
                } else if (value > max) {
                    message = `Only ${max} available`;
                }

                if (message) {
                    input.classList.add('is-invalid');
                    feedback.textContent = message;
                    hasError = true;
                } else {
                    input.classList.remove('is-invalid');
                    feedback.textContent = '';
                }
            });

            updateBtn.disabled = hasError;

            if (hasError) {
                checkoutBtn.classList.add('disabled');
                checkoutBtn.setAttribute('aria-disabled', 'true');
            } else {
                checkoutBtn.classList.remove('disabled');
                checkoutBtn.removeAttribute('aria-disabled');
            }
        };

        qtyInputs.forEach(input => {
            input.addEventListener('input', validate);
        });

        checkoutBtn.addEventListener('click', preventCheckout);

        validate();

        // Hook remove confirmation for any cart.remove link
        if (modalEl && confirmBtn) {
            const modal = new bootstrap.Modal(modalEl);
            document.querySelectorAll('a[href*="route=cart.remove"]').forEach((link) => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    pendingRemoveUrl = link.getAttribute('data-remove-url') || link.getAttribute('href');
                    modal.show();
                });
            });
            confirmBtn.addEventListener('click', () => {
                if (pendingRemoveUrl) window.location.href = pendingRemoveUrl;
            });
        }
    });
</script>
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
