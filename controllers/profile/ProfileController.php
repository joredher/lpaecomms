<?php

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
}