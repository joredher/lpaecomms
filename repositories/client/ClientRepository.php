<?php

use repositories\BaseRepository;
require_once 'repositories/BaseRepository.php';

class ClientRepository extends BaseRepository
{
    public function __construct() {
        parent::__construct('lpa_clients');
    }

    /**
     * Find a client by linked user ID
     */
    public function findByUserId($userId) {
        return $this->findWhere('lpa_clients_fk_user_id', $userId);
    }

    /**
     * Create a new client from user session data
     */
    public function createFromUser(array $user): bool {
        return $this->create([
            'lpa_clients_fk_user_id' => $user['lpa_users_ID'],
            'lpa_clients_firstname' => $user['lpa_user_firstname'] ?? 'Unknown',
            'lpa_clients_lastname' => $user['lpa_user_lastname'] ?? '',
            'lpa_client_email' => $user['lpa_user_email'] ?? ''
        ]);
    }

    /**
     * Create a new client record based on the billing address form
     * Used during checkout to save billing details linked to user
     */
    public function createFromBillingForm(array $data): int|false {
        $result = $this->create([
            'lpa_clients_firstname'     => $data['firstname'],
            'lpa_clients_lastname'      => $data['lastname'] ?? '',
            'lpa_client_company'        => $data['company'] ?? '',
            'lpa_client_street'         => $data['street'],
            'lpa_client_apartment'      => $data['apartment'] ?? '',
            'lpa_client_city'           => $data['city'],
            'lpa_client_phone'          => $data['phone'],
            'lpa_client_email'          => $data['email'],
            'lpa_clients_fk_user_id'    => $data['user_id']
        ]);

        // Return the ID of the new client if successful
        if ($result) {
            return $this->conn->lastInsertId();
        }

        return false;
    }

}
