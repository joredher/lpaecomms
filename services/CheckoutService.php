<?php

require_once __DIR__ . '/../bootstrap.php';

loadRepo('repositories/client/ClientRepository.php');
loadRepo('repositories/invoice/InvoiceRepository.php');
loadRepo('services/InvoiceWorkflow.php');
loadRepo('helpers/mail.php');

class CheckoutService
{
    private ClientRepository $clientRepo;
    private InvoiceRepository $invoiceRepo;
    private InvoiceWorkflow $workflow;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->clientRepo = new ClientRepository();
        $this->invoiceRepo = new InvoiceRepository();
        $this->workflow = new InvoiceWorkflow();
    }

    /**
     * @return array{success:bool,status:int,message:string,data?:array,errors?:array}
     */
    public function process(array $payload): array
    {
        if (!isset($_SESSION['user']['id'])) {
            return [
                'success' => false,
                'status' => 401,
                'message' => 'Authentication required.',
            ];
        }

        if (empty($_SESSION['cart'])) {
            return [
                'success' => false,
                'status' => 400,
                'message' => 'Your cart is empty.',
            ];
        }

        $user = $_SESSION['user'];
        $billingData = [
            'user_id' => $user['id'],
            'firstname' => trim($payload['firstname'] ?? ''),
            'lastname' => trim($payload['lastname'] ?? ''),
            'street' => trim($payload['street'] ?? ''),
            'apartment' => trim($payload['apartment'] ?? ''),
            'city' => trim($payload['city'] ?? ''),
            'zipcode' => trim($payload['zipcode'] ?? ''),
            'phone' => trim($payload['phone'] ?? ''),
            'email' => trim($payload['email'] ?? ''),
        ];

        $errors = [];
        foreach (['firstname', 'street', 'city', 'phone', 'email'] as $field) {
            if ($billingData[$field] === '') {
                $errors[$field] = ucfirst($field) . ' is required.';
            }
        }
        if ($errors) {
            return [
                'success' => false,
                'status' => 422,
                'message' => 'Missing required billing information.',
                'errors' => $errors,
            ];
        }

        $saveInfo = isset($payload['save_info']) && ($payload['save_info'] === 'on' || $payload['save_info'] === '1');
        $_SESSION['save_checkout_info'] = $saveInfo ? 1 : 0;
        $billingData['consent'] = $_SESSION['save_checkout_info'];

        $paymentMethod = strtolower(trim($payload['payment_method'] ?? 'card'));
        if (!in_array($paymentMethod, ['card', 'cod'], true)) {
            $paymentMethod = 'card';
        }

        if ($paymentMethod === 'card') {
            foreach (['card_brand', 'card_last4', 'card_token'] as $field) {
                if (empty($payload[$field])) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required for card payments.';
                }
            }
            if ($errors) {
                return [
                    'success' => false,
                    'status' => 422,
                    'message' => 'Payment information is incomplete.',
                    'errors' => $errors,
                ];
            }
        }

        $exists = $this->clientRepo->findIfTheAddressValid($billingData['street'], $user['id']);
        $addressInfo = $exists[0] ?? null;

        if (!$addressInfo || $addressInfo['isValid'] !== true) {
            return [
                'success' => false,
                'status' => 422,
                'message' => 'The provided address must match a saved address. Please update your profile.',
            ];
        }

        $fullStreet = $addressInfo['data']['lpa_full_address'] ?? $billingData['street'];
        $billingId = $addressInfo['data']['lpa_fk_client_ID'] ?? null;
        if ($billingId && method_exists($this->clientRepo, 'updateConsent')) {
            $this->clientRepo->updateConsent($billingId, (int)$_SESSION['save_checkout_info']);
        }

        $total = (float)($payload['total'] ?? 0);
        $invoiceId = $this->workflow->handle([
            'client_id' => $billingId,
            'total' => $total,
            'address' => $fullStreet,
            'client_name' => $billingData['firstname'],
            'save_info' => $_SESSION['save_checkout_info'] ?? 0,
            'payment_method' => $paymentMethod,
            'card_brand' => $payload['card_brand'] ?? null,
            'card_last4' => $payload['card_last4'] ?? null,
        ], $_SESSION['cart']);

        $invoiceData = $this->invoiceRepo->getInvoiceWithItems($invoiceId, $user['id']);
        if ($invoiceData) {
            sendInvoiceConfirmationEmail($invoiceData);
        }

        $_SESSION['last_invoice_id'] = $invoiceId;
        unset($_SESSION['cart'], $_SESSION['total']);

        return [
            'success' => true,
            'status' => 201,
            'message' => 'Order placed successfully.',
            'data' => [
                'order_id' => $invoiceId,
                'redirect' => $this->orderUrl($invoiceId),
            ],
        ];
    }

    private function orderUrl(int $invoiceId): string
    {
        return '/checkout.confirmation?order=' . rawurlencode((string)$invoiceId);
    }
}
