<?php

declare(strict_types=1);

require_once __DIR__ . '/../../repositories/client/ClientRepository.php';
require_once __DIR__ . '/../../repositories/invoice/InvoiceRepository.php';
require_once __DIR__ . '/../../services/InvoiceWorkflow.php';
require_once __DIR__ . '/../../services/AddressService.php';
require_once __DIR__ . '/../../helpers/mail.php';
require_once __DIR__ . '/CartApiController.php';

final class CheckoutApiController
{
    public static function start(): void
    {
        $user = api_require_user();
        self::enforceCartFreshness();

        $clientRepo = new ClientRepository();
        $primaryClientId = $clientRepo->getPrimaryClientId($user['id']);
        $client = null;
        if ($primaryClientId) {
            $client = $clientRepo->findById($primaryClientId);
        }
        if (!$client) {
            $client = $clientRepo->findByUserId($user['id']);
        }

        ApiResponder::success([
            'cart' => CartApiController::summariseCart(),
            'savedAddress' => $client ? self::formatClientAddress($client) : null,
            'paymentMethods' => self::paymentMethods(),
        ]);
    }

    public static function saveAddress(): void
    {
        $user = api_require_user();
        $payload = api_read_json_body();
        $result = self::persistAddress($payload, $user);

        ApiResponder::success([
            'address' => $result['address'],
            'message' => 'Address saved successfully.',
        ]);
    }

    public static function process(): void
    {
        $user = api_require_user();
        self::enforceCartFreshness();

        if (empty($_SESSION['cart'])) {
            ApiResponder::error('Your cart is empty.', 422, 'empty_cart');
            return;
        }

        $data = api_read_json_body();
        $payment = $data['payment'] ?? [];
        $paymentMethod = strtolower((string)($payment['method'] ?? 'card'));
        if (!in_array($paymentMethod, ['card', 'cod'], true)) {
            ApiResponder::error('Unsupported payment method.', 422, 'invalid_payment_method');
            return;
        }

        if ($paymentMethod === 'card') {
            foreach (['cardBrand', 'cardLast4', 'cardToken'] as $field) {
                if (empty($payment[$field])) {
                    ApiResponder::error('Card details are required for card payments.', 422, 'validation_error');
                    return;
                }
            }
        }

        $addressResult = self::persistAddress($data['address'] ?? [], $user);
        $clientId = $addressResult['clientId'];
        $fullAddress = $addressResult['address']['formatted'];

        $_SESSION['save_checkout_info'] = !empty($data['saveInfo']) ? 1 : 0;

        $cartSummary = CartApiController::summariseCart();
        $workflow = new InvoiceWorkflow();

        $invoiceData = [
            'total' => $cartSummary['total'],
            'client_name' => $addressResult['address']['firstname'] . ' ' . $addressResult['address']['lastname'],
            'client_id' => $clientId,
            'address' => $fullAddress,
            'payment_method' => $paymentMethod,
            'card_brand' => $paymentMethod === 'card' ? (string)$payment['cardBrand'] : null,
            'card_last4' => $paymentMethod === 'card' ? (string)$payment['cardLast4'] : null,
            'save_info' => (int)($_SESSION['save_checkout_info'] ?? 0),
        ];

        try {
            $invoiceId = $workflow->handle($invoiceData, $_SESSION['cart']);
        } catch (\Throwable $exception) {
            ApiResponder::error('Unable to process order at this time.', 500, 'checkout_failed');
            return;
        }

        if (!$invoiceId) {
            ApiResponder::error('Unable to create invoice.', 500, 'checkout_failed');
            return;
        }

        $invoiceRepo = new InvoiceRepository();
        $confirmation = $invoiceRepo->getInvoiceWithItems((int)$invoiceId, (int)$user['id']);

        if ($confirmation) {
            try {
                sendInvoiceConfirmationEmail([
                    'invoice' => $confirmation['invoice'],
                    'items' => $confirmation['items'],
                    'totals' => $confirmation['totals'],
                ]);
            } catch (\Throwable $e) {
                // Non-blocking if email fails.
            }
        }

        unset($_SESSION['cart'], $_SESSION['cart_created_at'], $_SESSION['total']);

        ApiResponder::success([
            'confirmation' => $confirmation,
            'message' => 'Order processed successfully.',
        ]);
    }

    private static function enforceCartFreshness(): void
    {
        $limit = 30 * 60;
        if (isset($_SESSION['cart_created_at']) && (time() - $_SESSION['cart_created_at']) > $limit) {
            unset($_SESSION['cart'], $_SESSION['cart_created_at'], $_SESSION['total']);
            ApiResponder::error('Your cart session has expired.', 410, 'cart_expired');
            exit;
        }
    }

    private static function persistAddress(array $input, array $user): array
    {
        $required = ['firstname', 'street', 'city', 'zipcode', 'email'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                ApiResponder::error("Missing required address field: {$field}.", 422, 'validation_error');
                exit;
            }
        }

        $payload = [
            'firstname' => trim((string)$input['firstname']),
            'lastname' => trim((string)($input['lastname'] ?? '')),
            'email' => trim((string)$input['email']),
            'addressId' => trim((string)($input['addressId'] ?? '')),
            'address' => trim((string)($input['address'] ?? '')),
            'street' => trim((string)$input['street']),
            'apartment' => trim((string)($input['apartment'] ?? '')),
            'city' => trim((string)$input['city']),
            'zipcode' => trim((string)$input['zipcode']),
            'phone' => trim((string)($input['phone'] ?? '')),
            'user_id' => (int)$user['id'],
            'consent' => !empty($input['consent']) ? 1 : 0,
        ];

        if ($payload['addressId'] !== '') {
            $addressService = new AddressService();
            $structured = $addressService->getStructuredAddress($payload['addressId']);
            if ($structured) {
                $numberFrom = trim((string)($structured['streetNumberFrom'] ?? ''));
                $numberTo = trim((string)($structured['streetNumberTo'] ?? ''));
                $streetName = trim(((string)($structured['streetName'] ?? '')) . ' ' . ((string)($structured['streetType'] ?? '')));
                $range = trim($numberFrom . ($numberTo !== '' ? ' - ' . $numberTo : ''));
                $payload['street'] = trim($range . ' ' . $streetName);
                $payload['city'] = $structured['state'] ?? $payload['city'];
                $payload['zipcode'] = $structured['postcode'] ?? $payload['zipcode'];
                $payload['address'] = $structured['sla'] ?? $payload['address'];
                $payload['apartment'] = trim(((string)($structured['typeApt'] ?? '')) . ' ' . ((string)($structured['unitNumber'] ?? '')));
            }
        }

        $clientRepo = new ClientRepository();
        $existing = $clientRepo->existsClientWithSameData([
            'email' => $payload['email'],
            'user_id' => $payload['user_id'],
            'address' => $payload['address'] ?: $payload['street'],
        ]);

        if (!empty($existing)) {
            $clientId = (int)$existing[0]['lpa_clients_ID'];
        } else {
            $clientId = $clientRepo->createFromBillingForm($payload);
            if (!$clientId) {
                ApiResponder::error('Unable to save address.', 500, 'address_save_failed');
                exit;
            }
        }

        $clientRepo->addLpaUserClientAddressValid([
            'lpa_pid_address' => $payload['addressId'],
            'lpa_full_address' => $payload['address'] ?: $payload['street'],
            'lpa_fk_client_ID' => $clientId,
            'lpa_fk_users_ID' => $payload['user_id'],
        ]);

        $address = [
            'clientId' => $clientId,
            'firstname' => $payload['firstname'],
            'lastname' => $payload['lastname'],
            'street' => $payload['street'],
            'apartment' => $payload['apartment'],
            'city' => $payload['city'],
            'zipcode' => $payload['zipcode'],
            'phone' => $payload['phone'],
            'email' => $payload['email'],
            'formatted' => trim($payload['address'] ?: $payload['street']),
        ];

        return [
            'clientId' => $clientId,
            'address' => $address,
        ];
    }

    private static function paymentMethods(): array
    {
        return [
            [
                'id' => 'card',
                'label' => 'Pay with card',
                'requiresCard' => true,
            ],
            [
                'id' => 'cod',
                'label' => 'Cash on delivery',
                'requiresCard' => false,
            ],
        ];
    }

    private static function formatClientAddress(array $client): array
    {
        return [
            'clientId' => (int)($client['lpa_clients_ID'] ?? 0),
            'firstname' => $client['lpa_clients_firstname'] ?? '',
            'lastname' => $client['lpa_clients_lastname'] ?? '',
            'email' => $client['lpa_client_email'] ?? '',
            'street' => $client['lpa_client_street'] ?? '',
            'apartment' => $client['lpa_client_apartment'] ?? '',
            'city' => $client['lpa_client_city'] ?? '',
            'zipcode' => trim((string)($client['lpa_client_postcode'] ?? '')),
            'phone' => $client['lpa_client_phone'] ?? '',
            'formatted' => $client['lpa_client_address'] ?? '',
        ];
    }
}
