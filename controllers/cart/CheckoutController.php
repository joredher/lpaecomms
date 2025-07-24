<?php

require_once 'repositories/client/ClientRepository.php';
require_once 'repositories/invoice/InvoiceRepository.php';


class CheckoutController
{
    private ClientRepository $clientRepo;

    public function __construct() {
        $this->clientRepo = new ClientRepository();
    }

    public function start() {
        // Cart expiration logic (30 minutes)
        $cartExpiryLimit = 30 * 60; // 1800 seconds
        if (isset($_SESSION['cart_created_at']) && (time() - $_SESSION['cart_created_at']) > $cartExpiryLimit) {
            unset($_SESSION['cart'], $_SESSION['total'], $_SESSION['cart_created_at']);
            $_SESSION['flash_message'] = [
                'message' => '🕒 Your cart has expired after 30 minutes of inactivity.',
                'type' => 'warning'
            ];
            if (isset($_SESSION['from_checkout']) && $_SESSION['from_checkout']) {
                unset($_SESSION['from_checkout']);
            }
            header('Location: /products');
            exit;
        }

        // User must be logged in
        if (!isset($_SESSION['user']) || empty($_SESSION['cart'])) {
            $_SESSION['flash_message'] = [
                'message' => 'You must be logged in and have items in your cart.',
                'type' => 'warning'
            ];
            header('Location: /register');
            exit;
        }

        $user = $_SESSION['user'];
        $client = $this->clientRepo->findByUserId($user['id']);

        if (!$client) {
            $_SESSION['flash_message'] = [
                'message' => 'Please complete your customer profile before proceeding to checkout.',
                'type' => 'info'
            ];
            $_SESSION['from_checkout'] = true;
            header('Location: /profile.create');
            exit;
        }

        $pageContent = 'pages/client/checkout.php';
        include 'includes/layout.php';
    }


    public function process() {

        if (!isset($_SESSION['user']) || empty($_SESSION['cart'])) {
            $_SESSION['flash_message'] = [
                'message' => 'Your session expired or your cart is empty.',
                'type' => 'danger'
            ];
            header('Location: /products');
            exit;
        }

        $user = $_SESSION['user'];

        // 1. Collect billing address from form
        $billingData = [
            'user_id' => $user['id'],
            'firstname' => trim($_POST['firstname'] ?? ''),
            'lastname' => trim($_POST['lastname'] ?? ''),
            'street' => trim($_POST['street'] ?? ''),
            'apartment' => trim($_POST['apartment'] ?? ''),
            'city' => trim($_POST['city'] ?? ''),
            'zipcode' => trim($_POST['zipcode'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
        ];

        $addressData = $billingData['street'] . ' ' . $billingData['apartment'] . ' ' . $billingData['city'];
        $billingData['lpa_client_address'] = $addressData;

        // Validate required fields
        foreach (['firstname', 'street', 'city', 'phone', 'email'] as $field) {
            if (empty($billingData[$field])) {
                $_SESSION['flash_message'] = [
                    'message' => "⚠️ Missing required billing field: $field",
                    'type' => 'danger'
                ];
                header('Location: ?route=checkout');
                exit;
            }
        }

        // 2. Save billing info (temporary, specific to this invoice)
        $clientRepo = new ClientRepository();
        $billingId = $clientRepo->createFromBillingForm($billingData); // <- we’ll create this method

        // 3. Save invoice
        $invoiceRepo = new InvoiceRepository();
        $invoiceId = $invoiceRepo->createInvoice([
            'client_id' => $billingId,
            'total' => $_SESSION['total'] ?: 0,
            'status' => 'P', // P = Pending, A = Active, C = Cancel
            'address' => $addressData,
        ]);

        // 4. Save order items
        foreach ($_SESSION['cart'] as $item) {
            $invoiceRepo->addInvoiceItem([
                'invoice_id' => $invoiceId,
                'stock_id' => $item['id'],
                'name' => $item['name'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'amount' => $item['price'] * $item['quantity']
            ]);
        }

        // 5. Clear cart
        unset($_SESSION['cart'], $_SESSION['total']);

        $_SESSION['flash_message'] = [
            'message' => '✅ Your order was placed successfully!',
            'type' => 'success'
        ];

        header("Location: /checkout.confirmation&invoice_id=$invoiceId");
        exit;
    }

}