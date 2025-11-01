<?php
require_once __DIR__ . '/../../helpers/mail.php';
require_once __DIR__ . '/../../repositories/UserRepository.php';
require_once __DIR__ . '/../../services/AuthService.php';

class AuthController
{
    private UserRepository $userRepo;
    private AuthService $authService;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->userRepo = new UserRepository();
        $this->authService = new AuthService();
    }

    public function login()
    {
        $result = $this->authService->attemptLogin(
            $_POST['username'] ?? '',
            $_POST['password'] ?? ''
        );

        if ($result['success']) {
            if (!empty($result['data']['requires_validation'])) {
                $_SESSION['flash_message'] = [
                    'message' => $result['message'],
                    'type' => 'info',
                    'img' => 'assets/images/icons/info.png'
                ];
                header('Location: /login');
            } else {
                $redirectTo = $result['data']['redirect'] ?? '/home';
                header('Location: ' . $redirectTo);
            }
        } else {
            $_SESSION['flash_message'] = [
                'message' => '⚠️ ' . $result['message'],
                'type' => $result['status'] === 403 ? 'warning' : 'danger',
                'img' => 'assets/images/icons/error.png'
            ];
            $redirect = $result['status'] === 403 ? '/admin-login' : '/login';
            header('Location: ' . $redirect);
        }
        exit;
    }

    public function adminLogin(): void
    {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($username) || empty($password)) {
            $_SESSION['flash_message'] = [
                'message' => 'Please fill out all fields correctly.',
                'type' => 'danger',
                'img' => 'assets/images/icons/error.png'
            ];
            header('Location: /admin-login');
            exit;
        }

        $user = $this->userRepo->findByEmail($username);

        if ($user && password_verify($password, $user['lpa_user_password'])) {
            // Only allow admins (group 1) in this route
            if ((int)($user['lpa_fk_user_group_ID'] ?? 0) !== 1) {
                $_SESSION['flash_message'] = [
                    'message' => 'Clients must use the standard login.',
                    'type' => 'warning',
                    'img' => 'assets/images/icons/error.png'
                ];
                header('Location: /login');
                exit;
            }

            $_SESSION['user'] = [
                'id' => $user['lpa_users_ID'],
                'username' => $user['lpa_user_username'],
                'email' => $user['lpa_user_email'],
                'firstname' => $user['lpa_user_firstname'],
                'group' => $user['lpa_fk_user_group_ID']
            ];

            header('Location: /admin');
            exit;
        }

        $_SESSION['flash_message'] = [
            'message' => 'Invalid credentials.',
            'type' => 'danger',
            'img' => 'assets/images/icons/error.png'
        ];
        header('Location: /admin-login');
        exit;
    }

    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /register');
            exit;
        }

        $result = $this->authService->register($_POST);

        if ($result['success']) {
            $_SESSION['flash_message'] = [
                'message' => '🎉 ' . $result['message'] . ' Please check your email for verification instructions.',
                'type' => 'success'
            ];
            $email = strtolower(trim($_POST['email'] ?? ''));
            $firstname = trim($_POST['firstname'] ?? '');
            if ($email !== '' && $firstname !== '') {
                $newUser = $this->userRepo->findByEmail($email);
                if ($newUser) {
                    $this->generateToken($newUser, $firstname, $email);
                }
            }
            header('Location: /login');
        } else {
            $_SESSION['flash_message'] = [
                'message' => '⚠️ ' . $result['message'],
                'type' => $result['status'] === 409 ? 'warning' : 'danger'
            ];
            header('Location: /register');
        }
        exit;
    }

    public function logout()
    {
        $result = $this->authService->logout();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['flash_message'] = [
            'message' => $result['message'],
            'type' => 'info'
        ];

        header('Location: /home');
        exit;
    }

    public function forgot()
    {
        $email = $_POST['email'] ?? '';

        $_SESSION['flash_message'] = [
            'message' => 'There is no user with this email.',
            'type' => 'warning'
        ];

        if ($user = $this->userRepo->findByEmail($email)) {
            [$token, $expiresAt] = $this->getToken('+2 hour');

            $this->userRepo->saveVerificationToken($user['lpa_users_ID'], $token, $expiresAt);

            error_log("🔐 Token generated and saved: $token");

            // Build verification URL
            $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
                . '://' . $_SERVER['HTTP_HOST'];
            $reset_password = "$baseUrl/reset_password?key_rpu=$token";

            $sendSuccess = sendResetPasswordEmail([
                'to' => $email,
                'firstname' => $user['lpa_user_firstname'],
                'link_to_reset_password' => $reset_password
            ]);
            error_log("📤 Email sending to $email was " . ($sendSuccess ? 'successful' : 'unsuccessful'));

            $_SESSION['flash_message'] = [
                'message' => 'You have received a password reset link.',
                'type' => 'success'
            ];
        }
        header('Location: /login');

    }

    public function resetPassword()
    {
        $token = $_POST['token'] ?? null;
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (!$token || empty($password) || empty($confirmPassword)) {
            $_SESSION['flash_message'] = [
                'type' => 'warning',
                'message' => 'All fields are required.'
            ];
            header('Location: /reset_password?key_rpu=' . urlencode($token));
            exit;
        }

        if ($password !== $confirmPassword) {
            $_SESSION['flash_message'] = [
                'type' => 'danger',
                'message' => 'Passwords do not match.'
            ];
            header('Location: /reset_password?key_rpu=' . urlencode($token));
            exit;
        }

        $user = $this->userRepo->findByResetToken($token);

        if (!$user || strtotime($user['expires_at']) < time()) {
            $_SESSION['flash_message'] = [
                'type' => 'danger',
                'message' => 'Invalid or expired reset link.'
            ];
            header('Location: /forgot_password');
            exit;
        }

        $userId = $user['lpa_users_ID'];
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $this->userRepo->updatePassword($userId, $hashedPassword);
        $this->userRepo->deleteToken($token); // Eliminar token tras éxito

        $_SESSION['flash_message'] = [
            'type' => 'success',
            'message' => 'Password successfully updated. You can now log in.'
        ];
        header('Location: /login');
        exit;
    }

    public function changePassword()
    {
    }

    public function generateToken($user, $firstname, $email)
    {
        [$token, $expiresAt] = $this->getToken();

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


    public function getToken(string $time = '+1 hour'): array
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime($time));
        return array($token, $expiresAt);
    }


}
