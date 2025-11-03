<?php

declare(strict_types=1);

require_once __DIR__ . '/../../repositories/UserRepository.php';
require_once __DIR__ . '/../../repositories/client/ClientRepository.php';

final class AccountApiController
{
    public static function profile(): void
    {
        $user = api_require_user();

        $userRepo = new UserRepository();
        $dbUser = $userRepo->findById((int)$user['id']);
        $clientRepo = new ClientRepository();
        $client = $clientRepo->findByUserId((int)$user['id']);

        ApiResponder::success([
            'user' => [
                'id' => (int)$dbUser['lpa_users_ID'],
                'firstname' => $dbUser['lpa_user_firstname'],
                'lastname' => $dbUser['lpa_user_lastname'],
                'email' => $dbUser['lpa_user_email'],
            ],
            'client' => $client ? self::formatClient($client) : null,
        ]);
    }

    public static function updateProfile(): void
    {
        $user = api_require_user();
        $payload = api_read_json_body();

        $firstname = trim((string)($payload['firstname'] ?? ''));
        $lastname = trim((string)($payload['lastname'] ?? ''));
        $email = trim((string)($payload['email'] ?? ''));

        if ($firstname === '' || $email === '') {
            ApiResponder::error('First name and email are required.', 422, 'validation_error');
            return;
        }

        $userRepo = new UserRepository();
        if ($userRepo->emailExists($email, (int)$user['id'])) {
            ApiResponder::error('Email address is already in use.', 409, 'email_exists');
            return;
        }

        $userRepo->update((int)$user['id'], [
            'lpa_user_firstname' => $firstname,
            'lpa_user_lastname' => $lastname,
            'lpa_user_email' => $email,
        ]);

        $_SESSION['user']['firstname'] = $firstname;
        $_SESSION['user']['email'] = $email;

        $clientRepo = new ClientRepository();
        $client = $clientRepo->findByUserId((int)$user['id']);
        if ($client) {
            $clientRepo->update((int)$client['lpa_clients_ID'], [
                'lpa_clients_firstname' => $firstname,
                'lpa_clients_lastname' => $lastname,
                'lpa_client_email' => $email,
            ]);
        }

        ApiResponder::success([
            'message' => 'Profile updated successfully.',
            'user' => [
                'firstname' => $firstname,
                'lastname' => $lastname,
                'email' => $email,
            ],
        ]);
    }

    public static function changePassword(): void
    {
        $user = api_require_user();
        $data = api_read_json_body();

        $current = (string)($data['currentPassword'] ?? $data['current_password'] ?? '');
        $new = (string)($data['newPassword'] ?? $data['new_password'] ?? '');
        $confirm = (string)($data['confirmPassword'] ?? $data['confirm_password'] ?? '');

        if ($new === '' || $confirm === '') {
            ApiResponder::error('New password is required.', 422, 'validation_error');
            return;
        }

        if ($new !== $confirm) {
            ApiResponder::error('New password and confirmation do not match.', 422, 'validation_error');
            return;
        }

        $userRepo = new UserRepository();
        $dbUser = $userRepo->findById((int)$user['id']);
        if (!$dbUser || !password_verify($current, $dbUser['lpa_user_password'])) {
            ApiResponder::error('Current password is incorrect.', 422, 'invalid_password');
            return;
        }

        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $userRepo->updatePassword((int)$user['id'], $hashed);

        ApiResponder::success(['message' => 'Password updated successfully.']);
    }

    private static function formatClient(array $client): array
    {
        return [
            'id' => (int)$client['lpa_clients_ID'],
            'firstname' => $client['lpa_clients_firstname'] ?? '',
            'lastname' => $client['lpa_clients_lastname'] ?? '',
            'email' => $client['lpa_client_email'] ?? '',
            'address' => $client['lpa_client_address'] ?? '',
            'street' => $client['lpa_client_street'] ?? '',
            'apartment' => $client['lpa_client_apartment'] ?? '',
            'city' => $client['lpa_client_city'] ?? '',
            'zipcode' => $client['lpa_client_postcode'] ?? '',
            'phone' => $client['lpa_client_phone'] ?? '',
        ];
    }
}
