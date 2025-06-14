<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    $_SESSION['intended_route'] = '/checkout';

    $_SESSION['flash_message'] = [
        'message' => '⚠️ You must log in before checking out.',
        'type' => 'warning'
    ];

    header('Location: /login');
    exit;
}

require_once 'includes/config.php';

//require_once 'includes/pagination.php';
$conn = Database::getConnection();
$backUrl = $_SESSION['previous_page'];
?>

<!-- checkout.php -->
<div class="pd-cart-section container py-5">
    <div class="mb-5">
        <a href="<?= $backUrl ?>" class="pd-back-button d-flex align-items-center text-decoration-none">
            <img src="../../assets/images/icons/back.svg" alt="Back" class="me-2">
            <span>Back</span>
        </a>
    </div>
    <div class="checkout-header">
        <div class="checkout-title">Billing Details</div>
    </div>
    <div class="checkout-body">
        <form action="?route=checkout.process" method="POST" class="checkout-form">
            <div class="form-column">
                <div class="form-group-custom">
                    <label>First Name <span class="text-required">*</span></label>
                    <input type="text" name="firstname" class="input-style" required>
                </div>
                <div class="form-group-custom">
                    <label>Company Name</label>
                    <input type="text" name="company" class="input-style">
                </div>
                <div class="form-group-custom">
                    <label>Street Address <span class="text-required">*</span></label>
                    <input type="text" name="street" class="input-style" required>
                </div>
                <div class="form-group-custom">
                    <label>Apartment, floor, etc. (optional)</label>
                    <input type="text" name="apartment" class="input-style">
                </div>
                <div class="form-group-custom">
                    <label>Town/City <span class="text-required">*</span></label>
                    <input type="text" name="city" class="input-style" required>
                </div>
                <div class="form-group-custom">
                    <label>Phone Number <span class="text-required">*</span></label>
                    <input type="tel" name="phone" class="input-style" required>
                </div>
                <div class="form-group-custom">
                    <label>Email Address <span class="text-required">*</span></label>
                    <input type="email" name="email" class="input-style" required>
                </div>
                <div class="option-group">
                    <input type="checkbox" name="save_info" id="saveInfo">
                    <label for="saveInfo" class="text-detail">Save this information for faster check-out next time</label>
                </div>
            </div>

            <div class="order-summary-container">
                <div class="order-summary">
                    <?php $cart = $_SESSION['cart'] ?? []; $total = 0; ?>
                    <?php foreach ($cart as $item): ?>
                        <div class="item-line">
                            <img class="item-image" src="<?= $item['image'] ?>" alt="<?= $item['name'] ?>">
                            <div class="item-detail">
                                <div class="text-detail"><?= htmlspecialchars($item['name']) ?></div>
                                <div class="text-detail">$<?= number_format($item['price'], 2) ?></div>
                            </div>
                        </div>
                        <?php $total += $item['price'] * $item['quantity']; ?>
                    <?php endforeach; ?>
                </div>

                <div class="summary-box">
                    <div class="summary-line">
                        <span class="text-detail">Subtotal:</span>
                        <span class="text-detail">$<?= number_format($total, 2) ?></span>
                    </div>
                    <div class="summary-line">
                        <span class="text-detail">Shipping:</span>
                        <span class="text-detail">Free</span>
                    </div>
                    <div class="summary-line">
                        <strong class="text-detail">Total:</strong>
                        <strong class="text-detail">$<?= number_format($total, 2) ?></strong>
                    </div>
                </div>

                <div class="option-group">
                    <input type="radio" name="payment_method" id="bank" value="bank" checked>
                    <label for="bank" class="text-detail">Bank</label>
                </div>
                <div class="card-icons">
                    <img src="../../assets/images/cards/visa.png" alt="Visa">
                    <img src="../../assets/images/cards/mastercard.png" alt="Mastercard">
                    <img src="../../assets/images/cards/amex.png" alt="Amex">
                    <img src="../../assets/images/cards/bkash.png" alt="Bkash">
                </div>
                <div class="option-group">
                    <input type="radio" name="payment_method" id="cod" value="cod">
                    <label for="cod" class="text-detail">Cash on delivery</label>
                </div>

                <div class="btn-place-order">
                    <button type="submit">Place Order</button>
                </div>
            </div>
        </form>
    </div>

</div>

