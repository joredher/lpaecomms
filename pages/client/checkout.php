<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use Lpaecomms\Database;
use Lpaecomms\Repositories\ClientRepository;

if (!isset($_SESSION['user'])) {
    $_SESSION['intended_route'] = '/checkout';

    $_SESSION['flash_message'] = [
        'message' => '⚠️ You must log in before checking out.',
        'type' => 'warning'
    ];

    header('Location: /login');
    exit;
}

$conn = Database::getConnection();
$backUrl = $_SESSION['previous_page'] ?? '/home';

$client = (new ClientRepository())->findByUserId($_SESSION['user']['id']);

$clientData = [
    'firstname' => $client['lpa_clients_firstname'] ?? '',
    'lastname' => $client['lpa_clients_lastname'] ?? '',
    'email' => $client['lpa_client_email'] ?? '',
    'address' => $client['lpa_client_address'] ?? '',
    'street' => $client['lpa_client_street'] ?? '',
    'apartment' => $client['lpa_client_apartment'] ?? '',
    'city' => $client['lpa_client_city'] ?? '',
    'zipcode' => trim($client['lpa_client_postcode'] ?? ''),
    'phone' => ($client['lpa_client_phone'] || ((string) $client['lpa_client_phone'] !== '0')) ? $client['lpa_client_phone'] : ""
];

$valueTotal = 0;


?>

<!-- checkout.php -->
<div class="pd-cart-section container py-5" id="checkout-section">
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
        <form action="/checkout.process" method="POST" class="checkout-form">
            <div class="form-column">
                <div class="form-group-custom">
                    <label for="firstname">First Name <span class="text-required">*</span></label>
                    <input type="text" id="firstname" name="firstname" class="input-style" required value="<?= ($clientData['firstname']) ?>">
                </div>
                <div class="form-group-custom">
                    <label for="lastname">Last Name</label>
                    <input type="text" id="lastname" name="lastname" class="input-style" value="<?= ($clientData['lastname']) ?>">
                </div>
                <div class="form-group-custom  position-relative">
                    <label class="form-label" for="autocomplete-address">Address</label>
                    <input type="hidden" name="address-id" id="address-id">
                    <input
                            type="text"
                            class="input-style"
                            placeholder="Type your address"
                            name="address"
                            id="autocomplete-address"
                            value="<?= ($clientData['address']) ?>"
                            autocomplete="off"
                    >
                    <ul id="suggestions" class="list-group position-absolute" style="z-index: 10;"></ul>
                    <!--            <ul id="suggestions" class="list-group mt-2 position-absolute w-100 z-3"></ul>-->
                </div>
                <div class="form-group-custom">
                    <label for="street">Street Address <span class="text-required">*</span></label>
                    <input type="text" id="street" name="street" value="<?= ($clientData['street']) ?>" class="input-style" required>
                </div>
                <div class="form-group-custom">
                    <label for="apartment">Apartment, floor, etc. (optional)</label>
                <input type="text" id="apartment" name="apartment" value="<?= ($clientData['apartment']) ?>" class="input-style">
                </div>
                <div class="form-group-custom">
                    <label for="city">Town/City <span class="text-required">*</span></label>
                    <input type="text" id="city" name="city" value="<?= ($clientData['city']) ?>" class="input-style" required>
                </div>
                <div class="form-group-custom">
                    <label for="zipcode">Postcode <span class="text-required">*</span></label>
                    <input type="text" id="zipcode" name="zipcode" value="<?= ($clientData['zipcode']) ?>" class="input-style" required>
                </div>
                <div class="form-group-custom">
                    <label for="phone">Phone Number <span class="text-required">*</span></label>
                    <input type="tel" id="phone" name="phone" class="input-style" maxlength="11"  max="999999999" required value="<?= ($clientData['phone']) ?>">
                </div>
                <div class="form-group-custom">
                    <label for="email">Email Address <span class="text-required">*</span></label>
                    <input type="email" id="email" name="email" class="input-style" required value="<?= ($clientData['email']) ?>">
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
                        <?php
                            $total += $item['price'] * $item['quantity'];
                            $valueTotal = $total;
                        ?>
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
                        <input type="hidden" name="total" id="total" value="<?= $valueTotal?>">
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

