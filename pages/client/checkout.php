<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../bootstrap.php';
loadRepo('repositories/client/ClientRepository.php');

if (!isset($_SESSION['user'])) {
    $_SESSION['intended_route'] = '/checkout';

    $_SESSION['flash_message'] = [
        'message' => 'You need an account to checkout. Please register to continue.',
        'type' => 'warning'
    ];

    header('Location: /register');
    exit;
}

require_once 'includes/config.php';

//require_once 'includes/pagination.php';
$conn = Database::getConnection();
$backUrl = $_SESSION['previous_page'] ?? '/home';

$repoClient = new ClientRepository();
$primaryClientId = $repoClient->getPrimaryClientId($_SESSION['user']['id']);
$client = $repoClient->findById($primaryClientId);

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
<?php $cartCreatedAt = $_SESSION['cart_created_at'] ?? time(); ?>
<div class="pd-cart-section container py-5" id="checkout-section" data-created-at="<?= (int)$cartCreatedAt ?>" data-timeout-seconds="1800">
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
                    <input type="hidden" name=" -id" id="address-id">
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
                    <input type="checkbox" name="save_info" id="saveInfo" checked>
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
                                <div class="text-detail" data-price-aud="<?= number_format((float)$item['price'], 2, '.', '') ?>">$<?= number_format((float)$item['price'], 2) ?> AUD</div>
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
                        <span class="text-detail" data-price-aud="<?= number_format((float)$total, 2, '.', '') ?>">$<?= number_format((float)$total, 2) ?> AUD</span>
                    </div>
                    <div class="summary-line">
                        <span class="text-detail">Shipping:</span>
                        <span class="text-detail">Free</span>
                    </div>
                    <div class="summary-line">
                        <input type="hidden" name="total" id="total" value="<?= $valueTotal?>">
                        <strong class="text-detail">Total:</strong>
                        <strong class="text-detail" data-price-aud="<?= number_format((float)$total, 2, '.', '') ?>">$<?= number_format((float)$total, 2) ?> AUD</strong>
                    </div>
                </div>

                <!-- Payment method -->
                <div class="option-group mt-3">
                    <input type="radio" name="payment_method" id="card" value="card" checked>
                    <label for="card" class="text-detail">Pay with card</label>
                </div>
                <div class="payment-card-box" id="cardBox">
                    <div class="d-flex align-items-center gap-2 mb-2 card-icons">
                        <img src="../../assets/images/cards/visa.png" alt="Visa">
                        <img src="../../assets/images/cards/mastercard.png" alt="Mastercard">
                        <img src="../../assets/images/cards/amex.png" alt="Amex">
                    </div>
                    <div class="d-flex flex-column flex-md-row gap-2 align-items-stretch">
                        <select id="cardSelect" class="form-select" aria-label="Saved cards">
                            <option value="">Select a saved test card…</option>
                        </select>
                        <button type="button" class="btn btn-outline-secondary" id="addCardBtn">Add Test Card</button>
                    </div>
                    <input type="hidden" name="card_brand" id="card_brand">
                    <input type="hidden" name="card_last4" id="card_last4">
                    <input type="hidden" name="card_token" id="card_token">
                </div>
                <div class="option-group mt-2">
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

<!-- Cart Expiration Modal -->
<div class="modal fade lpa-modal" id="cartExpiryModal" tabindex="-1" aria-labelledby="cartExpiryTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cartExpiryTitle">Are you still there?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Your cart will expire soon due to inactivity. Do you want to keep it?
            </div>
            <div class="modal-footer">
                <a href="/products" class="btn btn-outline-secondary">Leave</a>
                <button type="button" class="btn btn-primary" id="keepCartAliveBtn">Keep my cart</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Test Card Modal -->
<div class="modal fade lpa-modal" id="cardModal" tabindex="-1" aria-labelledby="cardModalTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cardModalTitle">Add Test Card</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="cardBrand">Brand</label>
                        <select id="cardBrand" class="form-select">
                            <option value="visa">Visa</option>
                            <option value="mastercard">Mastercard</option>
                            <option value="amex">American Express</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="cardNumber1">Card number</label>
                        <div class="d-flex gap-2">
                            <input id="cardNumber1" class="form-control text-center card-part" maxlength="4" inputmode="numeric" pattern="[0-9]*" aria-label="Card number part 1">
                            <input id="cardNumber2" class="form-control text-center card-part" maxlength="4" inputmode="numeric" pattern="[0-9]*" aria-label="Card number part 2">
                            <input id="cardNumber3" class="form-control text-center card-part" maxlength="4" inputmode="numeric" pattern="[0-9]*" aria-label="Card number part 3">
                            <input id="cardNumber4" class="form-control text-center card-part" maxlength="4" inputmode="numeric" pattern="[0-9]*" aria-label="Card number part 4">
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="cardExpiry">Expiry (MM/YY)</label>
                        <input id="cardExpiry" class="form-control" placeholder="12/30" inputmode="numeric" aria-label="Expiry">
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="cardCvc">CVC</label>
                        <input id="cardCvc" class="form-control" placeholder="123" maxlength="4" inputmode="numeric" aria-label="CVC">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="cardHolder">Card holder</label>
                        <input id="cardHolder" class="form-control uppercase" placeholder="JOHN APPLESEED">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveCardBtn">Save Card</button>
            </div>
        </div>
    </div>
</div>

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


