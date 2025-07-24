<?php

use repositories\BaseRepository;
loadRepo('repositories/BaseRepository.php');

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
            'lpa_client_address'        => $data['address'] ?? '',
            'lpa_client_street'         => $data['street'],
            'lpa_client_apartment'      => $data['apartment'] ?? '',
            'lpa_client_country'        => 'Australia',
            'lpa_client_city'           => $data['city'],
            'lpa_client_postcode'       => $data['postcode'],
            'lpa_client_phone'          => $data['phone'] ?? 000,
            'lpa_client_email'          => $data['email'],
            'lpa_clients_fk_user_id'    => $data['user_id']
        ]);

        // Return the ID of the new client if successful
        if ($result) {
            return $this->conn->lastInsertId();
        }

        return false;
    }

    public function addLpaUserClientAddressValid (array $data): bool
    {
        $baseRepo = new BaseRepository('lpa_user_client_address_valid');

        $exists = $baseRepo->findWhere('lpa_fk_client_ID', $data['lpa_fk_client_ID']) ?: null;

        if (!empty($exists)) {
            $baseRepo->delete($exists['id']);
        }

        $result =  $baseRepo->create($data);

        return !$result;
    }

    public function existsClientWithSameData($data): bool
    {
        $query = /** @lang text */
            "SELECT COUNT(*) as count FROM lpa_clients 
              WHERE lpa_client_email = :email 
              AND lpa_client_address = :address 
              AND lpa_clients_fk_user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':email' => $data['email'],
            ':address' => $data['address'],
            ':user_id' => $data['user_id'],
        ]);

        $result = $stmt->fetch();

        return $result['count'] > 0;
    }


}
