<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

require_once __DIR__ . '/../../bootstrap.php';

loadRepo('repositories/client/ClientRepository.php');
loadRepo('repositories/UserRepository.php');
loadRepo('services/AddressService.php');
loadRepo('middleware/AuthMiddleware.php');
//require_once 'repositories/client/ClientRepository.php';
//require_once 'repositories/UserRepository.php';
//require_once 'services/AddressService.php';

class ProfileController
{
    private ClientRepository $clientRepo;

    public function __construct()
    {

        AuthMiddleware::authOnly();

        $this->clientRepo = new ClientRepository();
        if (session_status() === PHP_SESSION_NONE) session_start();
    }

    /**
     * Display the profile form, whether accessed from checkout or manually
     */
    public function create()
    {
        if (!isset($_SESSION['from_checkout'])) {
            header('Location: /home');
            exit;
        }

        if (!isset($_SESSION['user'])) {
            $_SESSION['flash_message'] = [
                'message' => 'Please log in to access your profile.',
                'type' => 'warning'
            ];
            header('Location: /login');
            exit;
        }

        $user = $_SESSION['user'];
        $client = $this->clientRepo->findByUserId($user['id']);

        // Ensure data structure exists
        if (!$client) {
            $client = [
                'lpa_clients_firstname' => '',
                'lpa_clients_lastname' => '',
                'lpa_client_email' => $user['email'],
                'lpa_client_address' => ''
            ];
        }

        // Include the account page layout
        $pageContent = 'pages/user/account.php';
        include 'includes/layout.php';
    }

    public function store()
    {
        $profileOption = $_POST['profile_option'];
        $user = $_SESSION['user'];

        $data = match ($profileOption) {
            'profile' => [
                'firstname' => $_POST['firstname'],
                'lastname' => $_POST['lastname'],
                'email' => $_POST['email'],
                'addressId' => $_POST['address-id'],
                'address' => $_POST['address'],
            ],
            'password' => [
                'current_password' => $_POST['current_password'],
                'new_password' => $_POST['new_password'],
                'confirm_password' => $_POST['confirm_password'],
            ],
            'address' => [
                'client_id' => $_POST['client_id']
            ]
        };

        $data = array_merge($data, ['user_id' => $user['id']]);
        $this->$profileOption($data);

        header('Location: /profile');
    }

    /**
     * @return void
     * @throws GuzzleException
     */
    public function profile($data)
    {
        $addressId = $data['addressId'];
        unset($data['addressId']);

        $clientRepo = new ClientRepository();
        $client = $clientRepo->existsClientWithSameData($data);
        $isClientExists = count($client) > 0;
        $addressData = [];
        $clientId = null;

        if (!$isClientExists) {
            unset($data['address']);

            $addressService = new AddressService();
            $addressData = $addressService->getStructuredAddress($addressId);
            var_dump('NO CLIENT', $addressId, $addressData);

            if ($addressData) {
                $fullStreet = $addressData['streetNumberFrom']
                    . (empty($addressData['streetNumberTo']) ? "" : " - " . $addressData['streetNumberTo'])
                    . " {$addressData['streetName']} {$addressData['streetType']} {$addressData['suburb']}";

                $fullApt = "{$addressData['typeApt']} {$addressData['unitNumber']}";

                $data = array_merge(
                    $data,
                    [
                        'street' => trim($fullStreet),
                        'postcode' => $addressData['postcode'],
                        'address' => $addressData['sla'],     // You can switch to MLA or SMLA if needed
                        'apartment' => trim($fullApt),
                        'city' => $addressData['state'],
                        'addressId' => $addressId
                    ]
                );

                $clientId = $clientRepo->createFromBillingForm($data);
            }
        }

        var_dump('LOG \n', $isClientExists ? $client[0]['lpa_clients_ID'] : '');
        $clientRepo->addLpaUserClientAddressValid([
            'lpa_pid_address' => $addressId,
            'lpa_full_address' => $isClientExists ? $client[0]['lpa_client_address'] : @$addressData['sla'],
            'lpa_fk_client_ID' => $isClientExists ? $client[0]['lpa_clients_ID'] : @$clientId,
            'lpa_fk_users_ID' => $data['user_id']
        ]);

        unset($addressId);

//        if (!$clientRepo->existsClientWithSameData($data)) {
//            unset($data['address']);
//
//            $client = new Client();
//            $response = $client->request('GET', "https://addressr.p.rapidapi.com/addresses/{$addressId}", [
//                'headers' => [
//                    'x-rapidapi-host' => 'addressr.p.rapidapi.com',
//                    'x-rapidapi-key' => 'fdb9e0567dmsha6e1dfa8a5d4f52p1f769bjsn932503b8f8e9',
//                ],
//            ]);
//
//            $dataResponse = json_decode($response->getBody(), true);
//
//            // Accessing structured parts
//            $unitNumber = $dataResponse['structured']['flat']['number'];
//            $streetNumberFrom = $dataResponse['structured']['number']['number'];
//            $streetNumberTo = $dataResponse['structured']['number']['last']['number'];
//            $streetName = $dataResponse['structured']['street']['name'];
//            $streetType = $dataResponse['structured']['street']['type']['name'];
//            $suburb = $dataResponse['structured']['locality']['name'];
//            $postcode = $dataResponse['structured']['postcode'];
//            $state = $dataResponse['structured']['state']['abbreviation'];
//            $typeApt = $dataResponse['structured']['flat']['type']['name'];
//
//            // Optional: Full address formats
//            $sla = $dataResponse['sla'];
//            $mla = $dataResponse['mla'];
//            $smla = $dataResponse['smla'];
//
//
//            $fullStreet = $streetNumberFrom . (empty($streetNumberTo) ? "" : " - $streetNumberTo ") . "$streetName $streetType $suburb";
//            $fullApt = "$typeApt $unitNumber";
//
//            $data = array_merge(
//                $data, [
//                    'street' => $fullStreet,
//                    'postcode' => $postcode,
//                    'address' => $sla,
//                    'apartment' => $fullApt,
//                    'city' => $state
//                ]
//            );
//            $clientId = $clientRepo->createFromBillingForm($data);
//            $clientRepo->addLpaUserClientAddressValid([
//                'lpa_pid_address' => $addressId,
//                'lpa_full_address' => $sla,
//                'lpa_fk_client_ID' => $clientId,
//            ]);
//
//        }
//
//
//        unset($addressId);

        $_SESSION['flash_message'] = [
            'message' => 'Your account has been updated.',
            'type' => 'info'
        ];

        if (isset($_SESSION['from_checkout']) && $_SESSION['from_checkout']) {
            unset($_SESSION['from_checkout']);

            header('Location: /checkout');
        }

    }

    public function address($data)
    {
        $clientId = $data['client_id'];
        $userId = $data['user_id'];

        $client = $this->clientRepo->findById($clientId);
        if (!$client) {
            $_SESSION['flash_message'] = [
                'message' => 'Address not found.',
                'type' => 'warning'
            ];
            return;
        }

        $this->clientRepo->addLpaUserClientAddressValid([
            'lpa_fk_client_ID' => $clientId,
            'lpa_fk_users_ID' => $userId,
            'lpa_full_address' => $client['lpa_client_address'],
            'lpa_pid_address' => $client['lpa_pid_address'],
        ], true);

        $_SESSION['flash_message'] = [
            'message' => 'Primary address updated.',
            'type' => 'success'
        ];
    }

    public function password($data)
    {
        $userRepo = new UserRepository();
        $user = $_SESSION['user'];

        // 1. Verifica que el usuario existe
        $dbUser = $userRepo->findById($user['id']);
        if (!$dbUser) {
            $_SESSION['flash_message'] = [
                'message' => 'User not found.',
                'type' => 'danger'
            ];
            header('Location: /profile');
            exit;
        }

        // 2. Valida contraseña actual
        if (!password_verify($data['current_password'], $dbUser['lpa_user_password'])) {
            $_SESSION['flash_message'] = [
                'message' => 'Current password is incorrect.',
                'type' => 'warning'
            ];
            header('Location: /profile');
            exit;
        }

        // 3. Valida que la nueva contraseña y confirmación coincidan
        if ($data['new_password'] !== $data['confirm_password']) {
            $_SESSION['flash_message'] = [
                'message' => 'New password and confirmation do not match.',
                'type' => 'warning'
            ];
            header('Location: /profile');
            exit;
        }

        // 4. Actualiza la contraseña en la base de datos
        $hashedPassword = password_hash($data['new_password'], PASSWORD_DEFAULT);
        $userRepo->updatePassword($user['id'], $hashedPassword);

        $_SESSION['flash_message'] = [
            'message' => 'Your password has been updated successfully.',
            'type' => 'success'
        ];
        header('Location: /profile');
        exit;
    }


    /**
     * Persist accessibility preferences for the logged-in user.
     * Accepts JSON body { prefs: {...} } or form field 'prefs' as JSON.
     */
    public function saveA11y(): void
    {
        AuthMiddleware::authOnly();
        header('Content-Type: application/json');

        if (!csrf_verify()) {
            http_response_code(419);
            echo json_encode(['ok' => false, 'message' => 'CSRF token mismatch']);
            return;
        }

        $raw = file_get_contents('php://input') ?: '';
        $data = null;
        if ($raw !== '') {
            $data = json_decode($raw, true);
        }
        if (!is_array($data)) {
            $data = ['prefs' => isset($_POST['prefs']) ? json_decode((string)$_POST['prefs'], true) : null];
        }

        $prefs = $data['prefs'] ?? null;
        if (!is_array($prefs)) {
            echo json_encode(['ok' => false, 'message' => 'Invalid payload']);
            return;
        }

        // Whitelist known keys only
        $allowed = ['textSize','contrast','darkMode','underlineLinks','reduceMotion','focusOutline'];
        $clean = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $prefs)) {
                $clean[$k] = $prefs[$k];
            }
        }

        $userId = (int)($_SESSION['user']['id'] ?? 0);
        if ($userId <= 0) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'message' => 'Unauthorized']);
            return;
        }

        $userRepo = new UserRepository();
        $ok = $userRepo->saveA11yPrefs($userId, $clean);
        if ($ok) {
            $_SESSION['a11y_prefs'] = $clean;
            echo json_encode(['ok' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['ok' => false]);
        }
    }

}
