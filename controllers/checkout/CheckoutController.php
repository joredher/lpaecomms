<?php

require_once __DIR__ . '/../../bootstrap.php';

require_once 'repositories/client/ClientRepository.php';
require_once 'repositories/invoice/InvoiceRepository.php';
loadRepo('services/AddressService.php');
loadRepo('middleware/AuthMiddleware.php');



class CheckoutController
{
    private ClientRepository $clientRepo;

    public function __construct()
    {
        $this->clientRepo = new ClientRepository();
        $this->invoiceRepo = new InvoiceRepository();
    }

    public function start()
    {
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


    public function process()
    {

        AuthMiddleware::authOnly();

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

        $addressId = trim($_POST['address-id'] ?? '');

//        $addressData = $billingData['street'] . ' ' . $billingData['apartment'] . ' ' . $billingData['city'];
//        $billingData['lpa_client_address'] = $addressData;

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


        $exists = $this->clientRepo->findIfTheAddressValid($billingData['street'], $user['id'])[0];

        if ($exists['isValid']):
            $fullStreet = $exists['data']['lpa_full_address'];
            $billingId = $exists['data']['lpa_fk_client_ID'];
        else:

            $addressService = new AddressService();
            $addressData = $addressService->getStructuredAddress($addressId);

            $billingData = array_merge($billingData, [
                'address' => $addressData['sla'],
                'addressId' => $addressId
            ]);

            // 2. Save billing info (temporary, specific to this invoice)
            $clientRepo = new ClientRepository();
            $billingId = $clientRepo->createFromBillingForm($billingData); // <- we’ll create this method

            $fullStreet = $addressData['streetNumberFrom']
                . (empty($addressData['streetNumberTo']) ? "" : " - " . $addressData['streetNumberTo'])
                . " {$addressData['streetName']} {$addressData['streetType']} {$addressData['suburb']}";

            $clientRepo->addLpaUserClientAddressValid([
                'lpa_pid_address' => $addressId,
                'lpa_full_address' => $addressData['sla'],
                'lpa_fk_client_ID' => $billingId,
                'lpa_fk_users_ID' =>$user['id']
            ], true);
        endif;

        // 3. Save invoice
        $invoiceId = $this->invoiceRepo->createInvoice([
            'client_id' => $billingId,
            'total' => $_POST['total'] ?: 0,
            'status' => 'P', // P = Pending, A = Active, C = Cancel
            'address' => $fullStreet,
            'client_name' => $billingData['firstname']
        ]);


        // 4. Save order items
        foreach ($_SESSION['cart'] as $key => $item) {
            $this->invoiceRepo->addInvoiceItem([
                'invoice_id' => $invoiceId,
                'stock_id' => (int) $key,
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

        header("Location: ".$this->orderUrl($invoiceId, true));
        exit;
    }

    /**
     * @throws JsonException
     */
    public function confirmation(): void
    {
        // must be logged in
        AuthMiddleware::authOnly();

        $userId = (int) $_SESSION['user']['id'];

        // 1) Read the invoice id from ?order= (or &order=)
        $invoiceId = 0;
        if (isset($_GET['order'])) {
            $invoiceId = (int) $_GET['order'];
        } elseif (!empty($_SERVER['QUERY_STRING'])) {
            // support router variants like /checkout.confirmation&order=123
            parse_str($_SERVER['QUERY_STRING'], $qs);
            if (!empty($qs['order'])) {
                $invoiceId = (int) $qs['order'];
            }
        }

        // 2) Fallback to last created invoice saved in session
        if ($invoiceId <= 0 && !empty($_SESSION['last_invoice_id'])) {
            $invoiceId = (int) $_SESSION['last_invoice_id'];
        }

        if ($invoiceId <= 0) {
            $this->renderNotFound('Missing order reference');
            return;
        }

        // 3) Load invoice scoped to current user
        $result = $this->invoiceRepo->getInvoiceWithItems($invoiceId, $userId);
        if (!$result) {
            $this->renderNotFound('Order not found or access denied');
            return;
        }

        // 4) Clear cart (idempotent)
        unset($_SESSION['cart'], $_SESSION['cart_created_at']);

        // 5) Prepare data for view
        $invoice = $result['invoice'];
        $items   = $result['items'];
        $totals  = $result['totals'];

        // 6) Optional JSON mode: /checkout.confirmation?order=123&accept=json
        if (strtolower($_GET['accept'] ?? '') === 'json') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => true,
                'invoice' => $invoice,
                'items' => $items,
                'totals' => $totals,
                'link' => $this->orderUrl((int)$invoice['id']), // same id
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            return;
        }

        // 7) Render page
        $title = 'Order Confirmation';
        $pageContent = 'pages/client/confirmation.php';
        include 'includes/layout.php';

        // 8) Cleanup
        unset($_SESSION['last_invoice_id']);
    }

    private function renderNotFound(string $reason = ''): void
    {
        http_response_code(404);
        $title = 'Order not found';
        $_SESSION['notFoundReason'] = $reason;
        $pageContent = 'pages/error/404.php';
        include 'includes/layout.php';
    }

    /** Build a URL that uses ?order=… (or &order=… if you prefer) */
    private function orderUrl(int $invoiceId, bool $questionStyle = true): string
    {
        $encoded = rawurlencode((string)$invoiceId);
        return $questionStyle
            ? "/checkout.confirmation?order={$encoded}"
            : "/checkout.confirmation&order={$encoded}";
    }

}