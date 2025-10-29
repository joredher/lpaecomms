<?php

use repositories\BaseRepository;

loadRepo('repositories/BaseRepository.php');

class ClientRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct('lpa_clients');
    }

    /**
     * Find a client by linked user ID
     */
    public function findByUserId($userId)
    {
        return $this->findWhere('lpa_clients_fk_user_id', $userId);
    }

    /**
     * Retrieve all addresses linked to a given user ID
     */
    public function findAllByUserId($userId): array
    {
        $stmt = $this->conn->prepare(
            /** @lang text */
            "SELECT * FROM {$this->table} WHERE lpa_clients_fk_user_id = :id"
        );
        $stmt->execute(['id' => $userId]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get the primary client ID for the given user if it exists
     */
    public function getPrimaryClientId($userId): ?int
    {
        $stmt = $this->conn->prepare(
            /** @lang text */
            "SELECT lpa_fk_client_ID FROM lpa_user_client_address_valid WHERE lpa_fk_users_ID = :uid LIMIT 1"
        );
        $stmt->execute(['uid' => $userId]);

        $id = $stmt->fetchColumn();

        return $id !== false ? (int)$id : null;
    }

    /**
     * Get the primary client ID for the given user if it exists
     */
    public function getAddressByClientId($userId): ?string
    {
        $stmt = $this->conn->prepare(
            /** @lang text */
            "SELECT lpa_full_address FROM lpa_user_client_address_valid WHERE lpa_fk_users_ID = :uid LIMIT 1"
        );
        $stmt->execute(['uid' => $userId]);

        $address = $stmt->fetchColumn();

        return $address !== false ? (string) $address : null;
    }

    /**
     * Create a new client from user session data
     */
    public function createFromUser(array $user): bool
    {
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
    public function createFromBillingForm(array $data): int|false
    {
        $result = $this->create([
            'lpa_clients_firstname' => $data['firstname'],
            'lpa_clients_lastname' => $data['lastname'] ?? '',
            'lpa_client_address' => $data['address'] ?? '',
            'lpa_client_street' => $data['street'],
            'lpa_client_apartment' => $data['apartment'] ?? '',
            'lpa_client_country' => 'Australia',
            'lpa_client_city' => $data['city'],
            'lpa_client_postcode' => $data['zipcode'],
            'lpa_client_phone' => $data['phone'] ?? 000,
            'lpa_client_email' => $data['email'],
            'lpa_consent_save_info' => (int)($data['consent'] ?? 0),
            'lpa_clients_fk_user_id' => $data['user_id'],
            'lpa_pid_address' => $data['addressId']
        ]);

        // Return the ID of the new client if successful
        if ($result) {
            return $this->conn->lastInsertId();
        }

        return false;
    }

    /**
     * Update consent flag on an existing client record
     */
    public function updateConsent(int $clientId, int $consent): bool
    {
        $base = new BaseRepository('lpa_clients');
        return $base->update($clientId, ['lpa_consent_save_info' => (int)$consent]);
    }

    public function addLpaUserClientAddressValid(array $data, bool $byUserId = false): bool
    {
        $baseRepo = new BaseRepository('lpa_user_client_address_valid');

        if (!$byUserId) {
            $query = /** @lang text */
                "SELECT * FROM lpa_user_client_address_valid 
              WHERE lpa_fk_client_ID = :id";
        } else {
            $query = /** @lang text */
                "SELECT * FROM lpa_user_client_address_valid
                    WHERE lpa_fk_users_ID = :id";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':id' => !$byUserId ? $data['lpa_fk_client_ID'] : $data['lpa_fk_users_ID'],
        ]);

        $exists = $stmt->fetch(PDO::FETCH_ASSOC);
//        $exists = $baseRepo->findWhere('lpa_fk_client_ID', $data['lpa_fk_client_ID']);


        if ($exists) {
            $baseRepo->delete($exists['id'], 'id');
        }

        $result = $baseRepo->create($data);

        return !$result;
    }

    public function findIfTheAddressValid($address, $userId): array
    {
        $stmt = $this->conn->prepare(/** @lang text */
            "SELECT *
            FROM lpa_user_client_address_valid
            WHERE lpa_fk_users_ID LIKE :userId");
        $stmt->execute([
            'userId' => $userId
        ]);

        $value = $stmt->fetch(PDO::FETCH_ASSOC);

        $addressClean1 = str_replace([' ', ',', '-'], '', strtolower($value['lpa_full_address']));
        $addressClean2 = str_replace([' ', ',', '-'], '', strtolower($address));


        return array([
            'data' => $value,
            'isValid' => str_contains($addressClean1, $addressClean2)
        ]);
    }

    public function existsClientWithSameData($data): array
    {
        $query = /** @lang text */
            "SELECT * FROM lpa_clients 
              WHERE lpa_client_email = :email
              AND lpa_clients_fk_user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':email' => $data['email'],
            ':user_id' => $data['user_id']
        ]);

        $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response = [];

        if (count($clients) > 0) {
            foreach ($clients as $client) {
                // Clean both addresses before comparing
                $inputAddress = strtolower(trim($data['address']));
                $storedAddress = strtolower(trim($client['lpa_client_address']));

                if ($inputAddress === $storedAddress) {
                    $response[] = $client;
                }
            }
        }

        return $response;
    }


}
