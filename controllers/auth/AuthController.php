<?php
require_once 'helpers/mail.php';
require_once 'repositories/UserRepository.php';

class AuthController
{
    private UserRepository $userRepo;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->userRepo = new UserRepository();
    }

    public function login()
    {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($username) || empty($password)) {
            $_SESSION['flash_message'] = [
                'message' => '⚠️ Please fill out all fields correctly.',
                'type' => 'danger'
            ];
            header('Location: /login');
            exit;
        }

        $user = $this->userRepo->findByEmail($username);

        if ($user && password_verify($password, $user['lpa_user_password'])) {
            $now = \Carbon\Carbon::now();
            $tokenCreatedAt = \Carbon\Carbon::parse($user['token_created_at'] ?? '1970-01-01');
            $interval = $now->diff($tokenCreatedAt);

            error_log('Password Validation: Passed!');
            //$_SESSION['pending_user_id'] = false;
            if (!$user['validation_token'] || $interval->days >= 7) {
                error_log('Token Process with code: Passed!');

                // Generate a new token
                $validationCode = random_int(100000, 999999);

                // Update user token and timestamp in DB
                $this->userRepo->updateValidationToken($user['lpa_users_ID'], $validationCode);

                // Send email via Mailtrap (helper or direct implementation)
                $sendSuccess = sendValidationCodeEmail([
                    'firstname' => $user['lpa_user_firstname'],
                    'username' => $user['lpa_user_username'],
                    'validationCode' => $validationCode,
                    'to' => $user['lpa_user_email']
                ]);
                error_log("📤 Email sending to" . $user['lpa_user_email'] . " was " . ($sendSuccess ? 'successful' : 'unsuccessful'));

                $_SESSION['pending_user_id'] = $user['lpa_users_ID'];
                header('Location: /login');
            } else {

                $_SESSION['user'] = [
                    'id' => $user['lpa_users_ID'],
                    'username' => $user['lpa_user_username'],
                    'email' => $user['lpa_user_email'],
                    'firstname' => $user['lpa_user_firstname'],
                    'group' => $user['lpa_fk_user_group_ID']
                ];

                $redirectTo = @$_SESSION['intended_route'] ?? '/home';
                unset($_SESSION['intended_route']);

                header("Location: $redirectTo");
            }

        } else {
            $_SESSION['flash_message'] = [
                'message' => '⚠️ Invalid credentials.',
                'type' => 'danger'
            ];
        }
        exit;
    }

    public function register()
    {

        if (isset($_SESSION['user'])) {
            // Optional: Flash message
            $_SESSION['flash_message'] = [
                'message' => 'You are already logged in.',
                'type' => 'info'
            ];
            header('Location: /home'); // Or ?route=dashboard
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=register');
            exit;
        }

        // Sanitize inputs
        $firstname = trim($_POST['firstname'] ?? '');
        $lastname = trim($_POST['lastname'] ?? '');
        $email = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));
        $phone = trim(preg_replace('/[^0-9]/', '', $_POST['phone'] ?? ''));
        $password = $_POST['password'] ?? '';
        $emailPrefix = explode('@', $email)[0];
        $currentYear = date('Y');
        $username = $emailPrefix . $currentYear;

        // Basic validation
        if (!$firstname || !$lastname || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
            $_SESSION['flash_message'] = [
                'message' => '⚠️ Please fill out all fields correctly.',
                'type' => 'danger'
            ];
            header('Location: /register');
            exit;
        }

        // Check if user already exists
        if ($this->userRepo->findByEmail($email)) {
            $_SESSION['flash_message'] = [
                'message' => '⚠️ Email already exists.',
                'type' => 'danger'
            ];
            header('Location: /register');
            exit;
        }

        // Create user
        $data = [
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'username' => $username,
            'phone' => $phone,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'group_id' => 2 // Default: client group
        ];

        if (!$this->userRepo->createUser($data)) {
            error_log("❌ Failed to create user in DB for: $email");
            $_SESSION['flash_message'] = [
                'message' => '❌ Failed to create user.',
                'type' => 'danger'
            ];
            header('Location: ?route=register');
            exit;
        }

        error_log("✅ User created: $email");
        // Retrieve new user
        $newUser = $this->userRepo->findByEmail($email);


        $this->generateToken($newUser, $firstname, $email);
//        // Login user
//        $_SESSION['user'] = [
//            'id' => $newUser['lpa_users_ID'],
//            'firstname' => $firstname,
//            'lastname' => $lastname,
//            'email' => $email,
//            'group_id' => $data['group_id']
//        ];

//        session_regenerate_id(true); // security best practice
//        $_SESSION['flash_message'] = [
//            'message' => '✅ Account created successfully!',
//            'type' => 'success'
//        ];


        $_SESSION['flash_message'] = [
            'message' => '📧 Please check your email to verify your account.',
            'type' => 'info'
        ];

        header('Location: /verify_email');
        exit;
    }

    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE): session_start(); endif;
        session_unset();
        session_destroy();

        // Optional: Regenerate session ID for security
        session_start();
        session_regenerate_id(true);

        $_SESSION['flash_message'] = [
            'message' => '👋 You’ve been logged out successfully.',
            'type' => 'info'
        ];

        header('Location: /home');
        exit;
    }

    public function forgotPassword()
    {
    }

    public function resetPassword()
    {
    }

    public function changePassword()
    {
    }

    public function generateToken($user, $firstname, $email)
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Save the token to the database
        $this->userRepo->saveVerificationToken($user['lpa_users_ID'], $token, $expiresAt);
        error_log("🔐 Token generated and saved: $token");

        // Build verification URL
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
            . '://' . $_SERVER['HTTP_HOST'];

        $verificationUrl = "$baseUrl/verify?token=$token";


        // Send the email using a helper
        $sendSuccess = sendVerificationEmail([
            'to' => $email,
            'firstname' => $firstname,
            'verificationUrl' => $verificationUrl]);
        error_log("📤 Email sending to $email was " . ($sendSuccess ? 'successful' : 'unsuccessful'));
    }

    public function verifyEmail()
    {
        if (!isset($_GET['token'])) {
            $_SESSION['flash_message'] = [
                'message' => '❌ Invalid verification link.',
                'type' => 'danger'
            ];
            header('Location: /login');
            exit;
        }

        $token = $_GET['token'];

        $user = $this->userRepo->verifyUserByToken($token);

        if ($user) {
            $_SESSION['flash_message'] = [
                'message' => '✅ Your email has been verified. Please log in.',
                'type' => 'success'
            ];
        } else {
            $_SESSION['flash_message'] = [
                'message' => '⚠️ Invalid or expired verification token.',
                'type' => 'danger'
            ];
        }

        header('Location: /login');
        exit;
    }

    public function verifyCode()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $code = trim($_POST['code_validation'] ?? '');
        $userId = $_SESSION['pending_user_id'] ?? null;

        if (!$userId) {
            $_SESSION['flash_message'] = [
                'message' => 'Session expired. Please log in again.',
                'type' => 'danger'
            ];
            header('Location: /login');
            exit;
        }

        if (empty($code)) {
            $_SESSION['flash_message'] = [
                'message' => '⚠️ Code is required.',
                'type' => 'danger'
            ];
            header('Location: /verify-code');
            exit;
        }

        $user = $this->userRepo->findById($userId);

        if ($user && $user['validation_token'] === $code) {
            // ✅ Successful verification
            $_SESSION['user'] = [
                'id' => $user['lpa_users_ID'],
                'username' => $user['lpa_user_username'],
                'email' => $user['lpa_user_email'],
                'firstname' => $user['lpa_user_firstname'],
                'group' => $user['lpa_fk_user_group_ID']
            ];

            // Clean up
            unset($_SESSION['pending_user_id']);
            $this->userRepo->updateValidationToken($user['lpa_users_ID'], '');

            $_SESSION['flash_message'] = [
                'message' => '✅ Login successful!',
                'type' => 'success'
            ];

            $redirectTo = @$_SESSION['intended_route'] ?? '/home';
            unset($_SESSION['intended_route']);

            header("Location: $redirectTo");
        } else {
            $_SESSION['flash_message'] = [
                'message' => '❌ Invalid code. Please try again.',
                'type' => 'danger'
            ];
            header('Location: /verify-code');
        }

        exit;
    }


}