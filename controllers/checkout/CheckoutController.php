<?php

require_once __DIR__ . '/../../bootstrap.php';

require_once 'repositories/client/ClientRepository.php';
require_once 'repositories/invoice/InvoiceRepository.php';
require_once 'helpers/mail.php';
loadRepo('services/AddressService.php');
loadRepo('middleware/AuthMiddleware.php');
loadRepo('services/InvoiceWorkflow.php');
require_once 'services/CheckoutService.php';



class CheckoutController
{
    private ClientRepository $clientRepo;
    private InvoiceRepository $invoiceRepo;
    private InvoiceWorkflow $workflow;
    private CheckoutService $checkoutService;

    public function __construct()
    {
        $this->clientRepo = new ClientRepository();
        $this->invoiceRepo = new InvoiceRepository();
        $this->workflow = new InvoiceWorkflow();
        $this->checkoutService = new CheckoutService();
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

        if (!AuthMiddleware::userOnly()) {
            AuthMiddleware::abort403('Checkout is only available to customer accounts.');
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

        if (!AuthMiddleware::userOnly()) {
            AuthMiddleware::abort403('Checkout is only available to customer accounts.');
        }

        $result = $this->checkoutService->process($_POST);

        if ($result['success']) {
            $_SESSION['flash_message'] = [
                'message' => '✅ ' . $result['message'],
                'type' => 'success'
            ];
            $redirect = $result['data']['redirect'] ?? $this->orderUrl((int)($result['data']['order_id'] ?? 0), true);
            header('Location: ' . $redirect);
        } else {
            $_SESSION['flash_message'] = [
                'message' => '⚠️ ' . $result['message'],
                'type' => $result['status'] === 422 ? 'warning' : 'danger'
            ];
            $fallback = $result['status'] === 401 ? '/login' : '/checkout';
            header('Location: ' . $fallback);
        }
        exit;
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
            if (!AuthMiddleware::userOnly()) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'status' => 403, 'error' => 'Only customer accounts may keep carts active']);
                return;
            }
            if (empty($_SESSION['cart'])) {
                echo json_encode(['ok' => false, 'error' => 'Cart is empty']);
                return;
            }
            $_SESSION['cart_created_at'] = time();
            $expiresAt = $_SESSION['cart_created_at'] + 1800; // 30 minutes
            echo json_encode(['ok' => true, 'expiresAt' => $expiresAt]);
        } catch (Throwable $e) {
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

        $acceptsJson = strtolower($_GET['accept'] ?? '') === 'json';
        if (!AuthMiddleware::userOnly()) {
            if ($acceptsJson) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'ok' => false,
                    'status' => 403,
                    'error' => 'Only customer accounts may view order confirmations.',
                ], JSON_THROW_ON_ERROR);
                return;
            }
            AuthMiddleware::abort403('Only customer accounts may view order confirmations.');
        }

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
        if ($acceptsJson) {
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
