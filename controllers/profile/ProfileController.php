<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

require_once 'repositories/client/ClientRepository.php';

class ProfileController
{
    private ClientRepository $clientRepo;

    public function __construct()
    {
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
            ],
        };

        $data = array_merge($data, ['user_id' => $user['id']]);
        $this->$profileOption($data);

        header('Location: /account');
    }

    /**
     * @return void
     * @throws GuzzleException
     */
    public function profile($data)
    {
        $addressId = $data['addressId'];
        unset($data['addressId']);



        $client = new Client();
        $response = $client->request('GET', "https://addressr.p.rapidapi.com/addresses/{$addressId}", [
            'headers' => [
                'x-rapidapi-host' => 'addressr.p.rapidapi.com',
                'x-rapidapi-key' => 'fdb9e0567dmsha6e1dfa8a5d4f52p1f769bjsn932503b8f8e9',
            ],
        ]);

        $dataResponse = json_decode($response->getBody(), true);

        // Accessing structured parts
        $unitNumber = $dataResponse['structured']['flat']['number'];
        $streetNumberFrom = $dataResponse['structured']['number']['number'];
        $streetNumberTo = $dataResponse['structured']['number']['last']['number'];
        $streetName = $dataResponse['structured']['street']['name'];
        $streetType = $dataResponse['structured']['street']['type']['name'];
        $suburb = $dataResponse['structured']['locality']['name'];
        $postcode = $dataResponse['structured']['postcode'];
        $state = $dataResponse['structured']['state']['abbreviation'];
        $typeApt = $dataResponse['structured']['flat']['type']['name'];

        // Optional: Full address formats
        $sla = $dataResponse['sla'];
        $mla = $dataResponse['mla'];
        $smla = $dataResponse['smla'];


        $fullStreet = $streetNumberFrom . (empty($streetNumberTo) ? "" : " - $streetNumberTo "). "$streetName $streetType $suburb" ;
        $fullApt = "$typeApt $unitNumber";

        $data = array_merge(
            $data, [
                'street' => $fullStreet,
                'postcode' => $postcode,
                'address' => $sla,
                'apartment' => $fullApt,
                'city' => $state
            ]
        );

        $clientRepo = new ClientRepository();

        if (!$clientRepo->existsClientWithSameData($data)) {
            $clientId = $clientRepo->createFromBillingForm($data);
            $clientRepo->addLpaUserClientAddressValid([
                'lpa_pid_address' => $addressId,
                'lpa_full_address' => $sla,
                'lpa_fk_client_ID' => $clientId,
            ]);
        }


        unset($addressId);

        $_SESSION['flash_message'] = [
            'message' => 'Your account has been updated.',
            'type' => 'info'
        ];

        if (isset($_SESSION['from_checkout']) && $_SESSION['from_checkout']) {
            unset($_SESSION['from_checkout']);

            header('Location: /checkout');
        }

    }


}