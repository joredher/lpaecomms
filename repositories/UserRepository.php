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
}
