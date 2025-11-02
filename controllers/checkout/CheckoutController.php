<?php

require_once __DIR__ . '/../../bootstrap.php';

require_once 'repositories/client/ClientRepository.php';
require_once 'repositories/invoice/InvoiceRepository.php';
require_once 'helpers/mail.php';
loadRepo('services/AddressService.php');
loadRepo('middleware/AuthMiddleware.php');
loadRepo('services/InvoiceWorkflow.php');


class CheckoutController
{
    private ClientRepository $clientRepo;
    private InvoiceRepository $invoiceRepo;
    private InvoiceWorkflow $workflow;

    public function __construct()
    {
        $this->clientRepo = new ClientRepository();
        $this->invoiceRepo = new InvoiceRepository();
        $this->workflow = new InvoiceWorkflow();
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
        $requestId = audit_request_id();
        $_SESSION['last_checkout_request_id'] = $requestId;
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

        // Audit view of checkout page (no PII)
        try {
            $lines = is_array($_SESSION['cart'] ?? null) ? count($_SESSION['cart']) : 0;
            $qty = 0; foreach (($_SESSION['cart'] ?? []) as $it) { $qty += (int)($it['quantity'] ?? 0); }
            audit_log('checkout_viewed', 'cart', null, [
                'lines' => $lines,
                'qty'   => $qty,
                'total' => (float)($_SESSION['total'] ?? 0),
            ]);
        } catch (Throwable $e) { /* ignore audit failure */ }

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

        // Optional preference: save checkout info for next time
        $saveInfo = isset($_POST['save_info']) && ($_POST['save_info'] === 'on' || $_POST['save_info'] === '1');
        $_SESSION['save_checkout_info'] = $saveInfo ? 1 : 0;
        // Also carry it with billing payload for repository methods
        $billingData['consent'] = $_SESSION['save_checkout_info'];

        $addressId = trim($_POST['address-id'] ?? '');

        // Payment method validation (server-side)
        $paymentMethod = strtolower(trim($_POST['payment_method'] ?? 'card'));
        if (!in_array($paymentMethod, ['card', 'cod'], true)) {
            $paymentMethod = 'card';
        }
        $cardBrand = trim($_POST['card_brand'] ?? '');
        $cardLast4 = trim($_POST['card_last4'] ?? '');
        $cardToken = trim($_POST['card_token'] ?? '');
        if ($paymentMethod === 'card' && ($cardBrand === '' || $cardLast4 === '' || $cardToken === '')) {
            $_SESSION['flash_message'] = [
                'message' => 'Please select or add a test card to pay.',
                'type' => 'warning'
            ];
            header('Location: /checkout');
            exit;
        }

        // Audit: process begin (no sensitive data)
        try {
            $lines = is_array($_SESSION['cart'] ?? null) ? count($_SESSION['cart']) : 0;
            $qty = 0; foreach (($_SESSION['cart'] ?? []) as $it) { $qty += (int)($it['quantity'] ?? 0); }
            audit_log('checkout_process_begin', 'cart', null, [
                'request_id' => $requestId,
                'lines' => $lines,
                'qty'   => $qty,
                'total' => (float)($_SESSION['total'] ?? 0),
                'payment_method' => $paymentMethod,
                'save_info' => (int)($_SESSION['save_checkout_info'] ?? 0),
                'csrf_present' => isset($_POST['_token']) ? 1 : 0,
            ]);
        } catch (Throwable $e) { /* ignore audit failure */ }

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
        try {
            $matchedId = $exists['data']['lpa_fk_client_ID'] ?? null;
            audit_log('address_checked', 'address', $addressId !== '' ? (string)$addressId : null, [
                'request_id' => $requestId,
                'exists' => (bool)($exists['isValid'] ?? false),
                'matched_client_id' => $matchedId ? (int)$matchedId : null,
            ]);
        } catch (Throwable $e) { /* ignore audit failure */ }

        $pdo = Database::getConnection();
        try {
            $pdo->beginTransaction();

            if ($exists['isValid'] === true) {
                $fullStreet = $exists['data']['lpa_full_address'];
                $billingId = $exists['data']['lpa_fk_client_ID'];
                // Update consent preference on the existing client record
                if (method_exists($this->clientRepo, 'updateConsent')) {
                    $this->clientRepo->updateConsent($billingId, (int)($_SESSION['save_checkout_info'] ?? 0));
                }

            } else {
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
                    'lpa_fk_users_ID' => $user['id']
                ], true);
                try { audit_log('address_created', 'client', (string)$billingId, ['request_id' => $requestId, 'address_id' => $addressId]); } catch (Throwable $e) { }
            }

            // 3-5. Persist invoice, items and mark as paid (workflow)
            try {
                $lines = is_array($_SESSION['cart'] ?? null) ? count($_SESSION['cart']) : 0;
                audit_log('invoice_create_attempt', 'invoice', null, [
                    'request_id' => $requestId,
                    'client_id' => (int)$billingId,
                    'total' => (float)($_POST['total'] ?? 0),
                    'payment_method' => $paymentMethod,
                    'lines' => $lines,
                ]);
            } catch (Throwable $e) { /* ignore audit failure */ }

            $invoiceId = $this->workflow->handle([
                'client_id' => $billingId,
                'total' => $_POST['total'] ?: 0,
                'address' => $fullStreet,
                'client_name' => $billingData['firstname'],
                'save_info' => $_SESSION['save_checkout_info'] ?? 0,
                'payment_method' => $paymentMethod,
                'card_brand' => $cardBrand ?: null,
                'card_last4' => $cardLast4 ?: null,
            ], $_SESSION['cart']);

            if (!$invoiceId) {
                throw new RuntimeException('Failed to create invoice');
            }

            // Commit DB changes before sending email
            $pdo->commit();

            // Audit success and invoice created
            try {
                $lines = is_array($_SESSION['cart'] ?? null) ? count($_SESSION['cart']) : 0;
                audit_log('invoice_created', 'invoice', (string)$invoiceId, [
                    'request_id' => $requestId,
                    'total' => (float)($_POST['total'] ?? 0),
                    'status' => ($paymentMethod === 'cod' ? 'P' : 'A'),
                    'lines' => $lines,
                ]);
                audit_log('checkout_success', 'invoice', (string)$invoiceId, [
                    'request_id' => $requestId,
                    'payment_method' => $paymentMethod,
                    'total' => (float)($_POST['total'] ?? 0),
                ]);
            } catch (Throwable $e) { /* ignore audit failure */ }

            // Send invoice confirmation email
            $invoiceData = $this->invoiceRepo->getInvoiceWithItems($invoiceId, $user['id']);
            if ($invoiceData && !sendInvoiceConfirmationEmail($invoiceData)) {
                error_log('❌ Failed to send invoice confirmation email.');
            }

            try { audit_log('email_send_attempted', 'invoice', (string)$invoiceId, ['request_id' => $requestId]); } catch (Throwable $e) { }
            // 6. Clear cart
            unset($_SESSION['cart'], $_SESSION['total']);
            try { audit_log('cart_cleared', 'cart', null, ['request_id' => $requestId]); } catch (Throwable $e) { }

            $_SESSION['flash_message'] = [
                'message' => '✅ Your order was placed successfully!',
                'type' => 'success'
            ];

            $_SESSION['flash_message']['title'] = 'Checkout';
            header("Location: " . $this->orderUrl($invoiceId, true));
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Checkout process failed: ' . $e->getMessage());
            try {
                audit_log('checkout_failed', 'invoice', isset($invoiceId) ? (string)$invoiceId : null, [
                    'request_id' => $requestId,
                    'error_class' => get_class($e),
                    'error_message' => substr($e->getMessage(), 0, 256),
                ]);
            } catch (Throwable $ignored) {}
            $_SESSION['flash_message'] = [
                'message' => 'There was a problem placing your order. Please try again.',
                'type' => 'danger'
            ];
            $_SESSION['flash_message']['title'] = 'Checkout';
            header('Location: /checkout');
            exit;
        }
    }

    /**
     * Keep the checkout/cart session alive a bit longer.
     * Returns JSON with a new expiry timestamp.
     */
    public function keepAlive(): void
    {
        header('Content-Type: application/json');
        try {
            AuthMiddleware::authOnly();
            if (empty($_SESSION['cart'])) {
                try { audit_log('keepalive_result', 'cart', null, ['ok' => 0, 'reason' => 'empty_cart', 'request_id' => audit_request_id()]); } catch (Throwable $e) {}
                echo json_encode(['ok' => false, 'error' => 'Cart is empty']);
                return;
            }
            $_SESSION['cart_created_at'] = time();
            $expiresAt = $_SESSION['cart_created_at'] + 1800; // 30 minutes
            try { audit_log('keepalive_result', 'cart', null, ['ok' => 1, 'expiresAt' => $expiresAt, 'request_id' => audit_request_id()]); } catch (Throwable $e) {}
            echo json_encode(['ok' => true, 'expiresAt' => $expiresAt]);
        } catch (Throwable $e) {
            try { audit_log('keepalive_result', 'cart', null, ['ok' => 0, 'reason' => 'exception', 'request_id' => audit_request_id()]); } catch (Throwable $ignored) {}
            echo json_encode(['ok' => false, 'error' => 'keepAlive failed']);
        }
    }

    /**
     * @throws JsonException
     */
    public function confirmation(): void
    {
        // must be logged in
        AuthMiddleware::authOnly();

        $userId = (int)$_SESSION['user']['id'];

        // 1) Read the invoice id from ?order= (or &order=)
        $invoiceId = 0;
        if (isset($_GET['order'])) {
            $invoiceId = (int)$_GET['order'];
        } elseif (!empty($_SERVER['QUERY_STRING'])) {
            // support router variants like /checkout.confirmation&order=123
            parse_str($_SERVER['QUERY_STRING'], $qs);
            if (!empty($qs['order'])) {
                $invoiceId = (int)$qs['order'];
            }
        }

        // 2) Fallback to last created invoice saved in session
        if ($invoiceId <= 0 && !empty($_SESSION['last_invoice_id'])) {
            $invoiceId = (int)$_SESSION['last_invoice_id'];
        }

        if ($invoiceId <= 0) {
            try { audit_log('confirmation_not_found', 'invoice', null, ['reason' => 'missing_param', 'request_id' => audit_request_id(), 'prev_request_id' => ($_SESSION['last_checkout_request_id'] ?? null)]); } catch (Throwable $e) {}
            $this->renderNotFound('Missing order reference');
            return;
        }

        // 3) Load invoice scoped to current user
        $result = $this->invoiceRepo->getInvoiceWithItems($invoiceId, $userId);
        if (!$result) {
            try { audit_log('confirmation_not_found', 'invoice', (string)$invoiceId, ['reason' => 'not_found_or_denied', 'request_id' => audit_request_id(), 'prev_request_id' => ($_SESSION['last_checkout_request_id'] ?? null)]); } catch (Throwable $e) {}
            $this->renderNotFound('Order not found or access denied');
            return;
        }

        // 4) Clear cart (idempotent)
        unset($_SESSION['cart'], $_SESSION['cart_created_at']);

        // 5) Prepare data for view
        $invoice = $result['invoice'];
        $items = $result['items'];
        $totals = $result['totals'];

        // 6) Optional JSON mode: /checkout.confirmation?order=123&accept=json
        try { audit_log('confirmation_viewed', 'invoice', (string)$invoiceId, ['request_id' => audit_request_id(), 'prev_request_id' => ($_SESSION['last_checkout_request_id'] ?? null)]); } catch (Throwable $e) {}
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
