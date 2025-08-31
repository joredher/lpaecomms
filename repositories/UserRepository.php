<?php

use repositories\BaseRepository;

loadRepo('repositories/BaseRepository.php');
loadRepo('repositories/UserRepository.php');

class UserRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct('lpa_users');
    }

    /**
     * Find user by email — for login or validation
     */
    public function findByEmail(string $email)
    {
        return $this->findWhere('lpa_user_email', $email);
    }

    /**
     * Find user by email — for login or validation
     */
    public function findByUsername(string $username)
    {
        return $this->findWhere('lpa_user_username', $username);
    }

    /**
     * Check if an email already exists. Optionally exclude a given user ID.
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE lpa_user_email = :email";
        $params = [':email' => $email];

        if ($excludeId) {
            $sql .= " AND lpa_users_ID != :id";
            $params[':id'] = $excludeId;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Find user by ID (override for cleaner name)
     */
    public function findById($id)
    {
        return parent::findById($id);
    }

    /**
     * Create a new user
     */
    public function createUser(array $data): bool
    {
        return $this->create([
            'lpa_user_email' => $data['email'],
            'lpa_user_password' => $data['password'], // hash before calling
            'lpa_user_firstname' => $data['firstname'] ?? '',
            'lpa_user_lastname' => $data['lastname'] ?? '',
            'lpa_fk_user_group_ID' => $data['group_id'] ?? 2,
            'lpa_user_username' => $data['username'] ?? '',
        ]);
    }

    public function saveVerificationToken(int $userId, string $token, string $expiresAt): bool
    {
        $stmt = $this->conn->prepare(/** @lang text */ "INSERT INTO lpa_verification_tokens (lpa_user_ID, token, expires_at) VALUES (?, ?, ?)");

        return $stmt->execute([$userId, $token, $expiresAt]);
    }

    public function verifyUserByToken(string $token): bool
    {
        $stmt = $this->conn->prepare(/** @lang text */ "
                                SELECT lpa_user_ID, expires_at 
                                FROM lpa_verification_tokens 
                                WHERE token = :token
                            ");
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return false; // Token not found
        }

        if (strtotime($row['expires_at']) < time()) {
            return false; // Token expired
        }

        $userId = $row['lpa_users_ID'];

        // Step 1: Mark user as verified
        $update = $this->conn->prepare(/** @lang text */ "
        UPDATE lpa_users SET is_verified = 1 WHERE lpa_users_ID = :id");
        $update->execute(['id' => $userId]);

        // Step 2: Delete or deactivate the token
        $delete = $this->conn->prepare(/** @lang text */ "
        DELETE FROM lpa_verification_tokens WHERE token = :token");
        $delete->execute(['token' => $token]);

        return true;
    }

    public function updateValidationToken(int $userId, string $token): bool
    {
        return $this->update($userId, [
            'validation_token' => $token,
            'token_created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * @param $userId
     * @param $hashedPassword
     * @return void
     */
    public function updatePassword($userId, $hashedPassword): void
    {
        $query = /** @lang text */
            "UPDATE lpa_users SET lpa_user_password = :password WHERE lpa_users_ID = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':password' => $hashedPassword,
            ':id' => $userId
        ]);
    }

    public function findByResetToken($token)
    {
        $stmt = $this->conn->prepare(/** @lang text */ "
            SELECT u.*, v.expires_at
            FROM lpa_users u
            INNER JOIN lpa_verification_tokens v ON u.lpa_users_ID = v.lpa_user_id
            WHERE v.token = :token
            LIMIT 1
        ");
        $stmt->execute([':token' => $token]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function deleteToken($token): void
    {
        $stmt = $this->conn->prepare(/** @lang text */ "DELETE FROM lpa_verification_tokens WHERE token = :token");
        $stmt->execute([
            'token' => $token
        ]);
    }
    /**
     * Count users for pagination with optional search and status filters
     */
    public function countAll(string $search = '', string $status = ''): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} u";
        $conditions = [];
        $params = [];

        if ($search !== '') {
            $conditions[] = "(u.lpa_user_username LIKE :term OR u.lpa_user_email LIKE :term OR u.lpa_user_firstname LIKE :term OR u.lpa_user_lastname LIKE :term)";
            $params[':term'] = "%{$search}%";
        }

        if ($status !== '') {
            $conditions[] = "u.lpa_user_status = :status";
            $params[':status'] = $status;
        }

        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, PDO::PARAM_STR);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Fetch users with pagination, search, and status filters
     */
    public function findPaginated(int $offset, int $limit, string $search = '', string $status = ''): array
    {
        $conditions = [];
        $params = [':offset' => $offset, ':limit' => $limit];

        if ($search !== '') {
            $conditions[] = "(u.lpa_user_username LIKE :term OR u.lpa_user_email LIKE :term OR u.lpa_user_firstname LIKE :term OR u.lpa_user_lastname LIKE :term)";
            $params[':term'] = "%{$search}%";
        }

        if ($status !== '') {
            $conditions[] = "u.lpa_user_status = :status";
            $params[':status'] = $status;
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $sql = "SELECT u.*, g.name AS group_name
                FROM {$this->table} u
                JOIN lpa_user_group g ON g.lpa_user_group_ID = u.lpa_fk_user_group_ID
                {$where}
                ORDER BY u.lpa_users_ID DESC
                LIMIT :offset, :limit";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Count users created since the provided date.
     */
    public function countUsersSince(string $date): int
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM {$this->table} WHERE token_created_at >= :date");
        $stmt->execute([':date' => $date]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Retrieve available user groups
     */
    public function getGroups(): array
    {
        $stmt = $this->conn->query('SELECT lpa_user_group_ID, name FROM lpa_user_group ORDER BY name');
        return $stmt->fetchAll();
    }

    /**
     * Update a user's status.
     */
    public function setStatus(int $id, string $status): bool
    {
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET lpa_user_status = :status WHERE lpa_users_ID = :id");
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }

}
