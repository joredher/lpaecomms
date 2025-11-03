<?php

declare(strict_types=1);

use Carbon\Carbon;

require_once __DIR__ . '/../../helpers/mail.php';
require_once __DIR__ . '/../../repositories/UserRepository.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

final class AuthApiController
{
    public static function me(): void
    {
        $user = api_current_user();

        if (!$user) {
            $pending = isset($_SESSION['pending_user_id']);
            ApiResponder::json([
                'success' => false,
                'authenticated' => false,
                'requiresValidation' => $pending,
            ], $pending ? 409 : 401);
            return;
        }

        ApiResponder::success([
            'authenticated' => true,
            'user' => self::formatUserPayload($user),
        ]);
    }

    public static function login(): void
    {
        $data = api_read_json_body();
        $email = strtolower(trim((string)($data['email'] ?? '')));
        $password = (string)($data['password'] ?? '');

        if ($email === '' || $password === '') {
            ApiResponder::error('Email and password are required.', 422, 'validation_error');
            return;
        }

        $userRepo = new UserRepository();
        $user = $userRepo->findByEmail($email);
        if (!$user) {
            $user = $userRepo->findByUsername($email);
        }

        if (!$user || !password_verify($password, $user['lpa_user_password'])) {
            ApiResponder::error('Invalid credentials.', 401, 'invalid_credentials');
            return;
        }

        if ((int)($user['lpa_fk_user_group_ID'] ?? 0) === 1) {
            ApiResponder::error('Admin accounts must use the admin login.', 403, 'forbidden');
            return;
        }

        $requiresValidation = false;
        if (AuthMiddleware::userOnly($user)) {
            $token = (string)($user['validation_token'] ?? '');
            $createdAt = $user['token_created_at'] ?? null;
            $ageDays = 999;
            if ($createdAt) {
                try {
                    $created = Carbon::parse($createdAt);
                    $ageDays = Carbon::now()->diffInDays($created);
                } catch (\Throwable $e) {
                    $ageDays = 999;
                }
            }
            if ($token === '' || $ageDays >= 7) {
                $requiresValidation = true;
            }
        }

        if ($requiresValidation) {
            $code = (string)random_int(100000, 999999);
            $userRepo->updateValidationToken((int)$user['lpa_users_ID'], $code);

            $sendSuccess = sendValidationCodeEmail([
                'firstname' => $user['lpa_user_firstname'],
                'username' => $user['lpa_user_username'],
                'validationCode' => $code,
                'to' => $user['lpa_user_email'],
            ]);

            $_SESSION['pending_user_id'] = (int)$user['lpa_users_ID'];

            ApiResponder::success([
                'requiresValidation' => true,
                'message' => $sendSuccess
                    ? 'A validation code has been sent to your email.'
                    : 'Validation code generated. Email delivery could not be confirmed.',
            ]);
            return;
        }

        self::finalizeLogin($userRepo, $user);
        ApiResponder::success([
            'requiresValidation' => false,
            'user' => self::formatUserPayload($_SESSION['user']),
        ]);
    }

    public static function validate(): void
    {
        $data = api_read_json_body();
        $code = trim((string)($data['code'] ?? ''));

        if ($code === '') {
            ApiResponder::error('Validation code is required.', 422, 'validation_error');
            return;
        }

        $userId = $_SESSION['pending_user_id'] ?? null;
        if (!$userId) {
            ApiResponder::error('Validation session expired. Please log in again.', 410, 'validation_expired');
            return;
        }

        $userRepo = new UserRepository();
        $user = $userRepo->findById((int)$userId);
        if (!$user || (string)($user['validation_token'] ?? '') !== $code) {
            ApiResponder::error('Invalid validation code.', 422, 'invalid_code');
            return;
        }

        $userRepo->updateValidationToken((int)$user['lpa_users_ID'], '');
        unset($_SESSION['pending_user_id']);

        self::finalizeLogin($userRepo, $user);

        ApiResponder::success([
            'requiresValidation' => false,
            'user' => self::formatUserPayload($_SESSION['user']),
        ]);
    }

    public static function logout(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
            }
            session_destroy();
        }

        session_start();
        session_regenerate_id(true);

        ApiResponder::success(['message' => 'Logged out successfully.']);
    }

    private static function finalizeLogin(UserRepository $userRepo, array $user): void
    {
        $_SESSION['user'] = [
            'id' => (int)$user['lpa_users_ID'],
            'username' => $user['lpa_user_username'],
            'email' => $user['lpa_user_email'],
            'firstname' => $user['lpa_user_firstname'],
            'group' => (int)$user['lpa_fk_user_group_ID'],
        ];
        session_regenerate_id(true);

        try {
            if (method_exists($userRepo, 'getA11yPrefs')) {
                $prefs = $userRepo->getA11yPrefs((int)$user['lpa_users_ID']);
                if (is_array($prefs)) {
                    $_SESSION['a11y_prefs'] = $prefs;
                } else {
                    unset($_SESSION['a11y_prefs']);
                }
            }
        } catch (\Throwable $e) {
            // Non-blocking if preferences fail to load.
        }
    }

    private static function formatUserPayload(array $user): array
    {
        return [
            'id' => (int)($user['id'] ?? 0),
            'email' => $user['email'] ?? null,
            'username' => $user['username'] ?? null,
            'firstname' => $user['firstname'] ?? null,
            'group' => (int)($user['group'] ?? 0),
        ];
    }
}
