<?php

require_once __DIR__ . '/../bootstrap.php';

loadRepo('repositories/UserRepository.php');
loadRepo('helpers/mail.php');
loadRepo('middleware/AuthMiddleware.php');

class AuthService
{
    private UserRepository $userRepo;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->userRepo = new UserRepository();
    }

    /**
     * Attempt to authenticate a client user.
     *
     * @return array{success:bool,status:int,message:string,data?:array,errors?:array}
     */
    public function attemptLogin(string $username, string $password): array
    {
        $username = trim($username);
        $password = trim($password);

        $errors = [];
        if ($username === '') {
            $errors['username'] = 'Username is required.';
        }
        if ($password === '') {
            $errors['password'] = 'Password is required.';
        }

        if ($errors) {
            return [
                'success' => false,
                'status' => 422,
                'message' => 'Please fill out all fields correctly.',
                'errors' => $errors,
            ];
        }

        $user = $this->userRepo->findByEmail($username);
        if (!$user || !password_verify($password, $user['lpa_user_password'])) {
            return [
                'success' => false,
                'status' => 401,
                'message' => 'Invalid credentials.',
            ];
        }

        if ((int)($user['lpa_fk_user_group_ID'] ?? 0) === 1) {
            return [
                'success' => false,
                'status' => 403,
                'message' => 'Admins must use the admin login.',
            ];
        }

        $now = \Carbon\Carbon::now();
        $tokenCreatedAt = \Carbon\Carbon::parse($user['token_created_at'] ?? '1970-01-01');
        $interval = $now->diff($tokenCreatedAt);

        if (AuthMiddleware::userOnly($user) && (!$user['validation_token'] || ($interval->days >= 7))) {
            $validationCode = random_int(100000, 999999);
            $this->userRepo->updateValidationToken($user['lpa_users_ID'], $validationCode);

            $emailSent = sendValidationCodeEmail([
                'firstname' => $user['lpa_user_firstname'],
                'username' => $user['lpa_user_username'],
                'validationCode' => $validationCode,
                'to' => $user['lpa_user_email'],
            ]);

            $_SESSION['pending_user_id'] = $user['lpa_users_ID'];

            return [
                'success' => true,
                'status' => $emailSent ? 202 : 200,
                'message' => $emailSent
                    ? 'A new validation code has been sent to your email.'
                    : 'Validation required. Unable to send the email, please check later.',
                'data' => [
                    'requires_validation' => true,
                    'user_id' => $user['lpa_users_ID'],
                ],
            ];
        }

        $_SESSION['user'] = [
            'id' => $user['lpa_users_ID'],
            'username' => $user['lpa_user_username'],
            'email' => $user['lpa_user_email'],
            'firstname' => $user['lpa_user_firstname'],
            'group' => $user['lpa_fk_user_group_ID'],
        ];

        $redirectTo = $_SESSION['intended_route'] ?? '/home';
        unset($_SESSION['intended_route']);

        return [
            'success' => true,
            'status' => 200,
            'message' => 'Login successful.',
            'data' => [
                'user' => $_SESSION['user'],
                'redirect' => $redirectTo,
            ],
        ];
    }

    /**
     * @param array<string,mixed> $payload
     * @return array{success:bool,status:int,message:string,data?:array,errors?:array}
     */
    public function register(array $payload): array
    {
        if (isset($_SESSION['user'])) {
            return [
                'success' => false,
                'status' => 409,
                'message' => 'You are already logged in.',
            ];
        }

        $firstname = trim($payload['firstname'] ?? '');
        $lastname = trim($payload['lastname'] ?? '');
        $email = strtolower(trim(filter_var($payload['email'] ?? '', FILTER_SANITIZE_EMAIL)));
        $phone = trim(preg_replace('/[^0-9]/', '', $payload['phone'] ?? ''));
        $password = (string)($payload['password'] ?? '');
        $emailPrefix = explode('@', $email)[0] ?? '';
        $currentYear = date('Y');
        $username = $emailPrefix . $currentYear;

        $errors = [];
        if ($firstname === '') {
            $errors['firstname'] = 'First name is required.';
        }
        if ($lastname === '') {
            $errors['lastname'] = 'Last name is required.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'A valid email is required.';
        }
        if (strlen($password) < 6) {
            $errors['password'] = 'Password must be at least 6 characters.';
        }

        if ($errors) {
            return [
                'success' => false,
                'status' => 422,
                'message' => 'Please fix the validation errors.',
                'errors' => $errors,
            ];
        }

        if ($this->userRepo->emailExists($email)) {
            $existingUser = $this->userRepo->findByEmail($email);
            sendEmailAlreadyExistsNotification([
                'firstname' => $existingUser['lpa_user_firstname'] ?? '',
                'email' => $email,
                'reset_password_url' => 'https://lpaecomms.test/forgot_password',
            ]);

            return [
                'success' => false,
                'status' => 409,
                'message' => 'Email already exists.',
            ];
        }

        $created = $this->userRepo->createUser([
            'firstname' => $firstname,
            'lastname' => $lastname,
            'username' => $username,
            'email' => $email,
            'phone' => $phone,
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        if (!$created) {
            return [
                'success' => false,
                'status' => 500,
                'message' => 'Unable to create user at this time.',
            ];
        }

        $newUser = $this->userRepo->findByEmail($email) ?: [];
        $userId = $newUser['lpa_users_ID'] ?? null;

        return [
            'success' => true,
            'status' => 201,
            'message' => 'Account created successfully.',
            'data' => [
                'user_id' => $userId,
                'username' => $username,
            ],
        ];
    }

    /**
     * @return array{success:bool,status:int,message:string}
     */
    public function logout(): array
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }

        return [
            'success' => true,
            'status' => 200,
            'message' => 'Logged out successfully.',
        ];
    }
}
