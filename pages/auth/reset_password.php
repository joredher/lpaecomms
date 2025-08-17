<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "<!-- ENTERED register.php -->";
$path = "assets/images/auth/";

use Lpaecomms\Database;
use Lpaecomms\Repositories\UserRepository;

$scriptDir = rtrim(dirname($_SERVER['PHP_SELF']), '/');
$basePath = preg_replace('#/pages(/.*)?$#', '', $scriptDir);
$basePath = $basePath === '' ? '/' : $basePath . '/';

$conn = Database::getConnection();

$token = $_GET['key_rpu'];

$userRepo = new UserRepository();

if (!$token || !($user = $userRepo->findByResetToken($token))) {

    $_SESSION['flash_message'] = [
        'message' => '❌ Invalid or expired reset link.',
        'type' => 'danger'
    ];
    header("location: {$basePath}home");

    die('❌ Invalid or expired reset link.');
}

if (\Carbon\Carbon::parse($user['expires_at'])->isPast()) {
    $_SESSION['flash_message'] = [
        'message' => '⏰ This link has expired.',
        'type' => 'danger'
    ];
    header("location: {$basePath}home");

    die('⏰ This link has expired.');
}


?>

<div class="auth-page-container">
    <div class="form-wrapper wrapper-login">
        <div class="logo-container text-center mb-4">
            <img src="assets/images/Logo.svg" alt="Ecommerce Logo" class="logo-img">
        </div>
        <div class="login">
            <form method="post" action="auth.resetPassword" class="m-0 position-relative">
                <!-- Hidden Token -->
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <!-- New Password -->
                <div class="form-group position-relative mb-2">
                    <label>
                        <img class="input-icon" src="<?= $path ?>password_key.svg" alt="Password">
                        <input class="form-control ps-5" type="password" name="password" id="password"
                               placeholder="New Password" required>
                    </label>
                </div>

                <!-- Confirm Password -->
                <div class="form-group position-relative mb-2">
                    <label>
                        <img class="input-icon" src="<?= $path ?>password_key.svg" alt="Confirm Password">
                        <input class="form-control ps-5" type="password" name="confirm_password" id="confirm_password"
                               placeholder="Confirm Password" required>
                    </label>
                    <small id="passwordMismatch" class="text-danger d-none">Passwords do not match.</small>
                </div>

                <button type="submit" class="submit-btn" id="reset-btn">
                    <span>Reset Password</span>
                </button>
            </form>
            <a class="sign-up-link text-decoration-none text-light-emphasis" href="login">Return to Log In</a>
        </div>
    </div>
</div>

<script>
    const password = document.getElementById('password');
    const confirm = document.getElementById('confirm_password');
    const button = document.getElementById('reset-btn');
    const mismatchMsg = document.getElementById('passwordMismatch');

    function validatePasswords() {
        const passVal = password.value.trim();
        const confirmVal = confirm.value.trim();

        if (passVal.length > 0 && confirmVal.length > 0) {
            if (passVal === confirmVal) {
                mismatchMsg.classList.add('d-none');
                button.classList.remove('d-none');
            } else {
                mismatchMsg.classList.remove('d-none');
                button.classList.add('d-none');
            }
        } else {
            mismatchMsg.classList.add('d-none');
            button.classList.add('d-none');
        }
    }

    password.addEventListener('input', validatePasswords);
    confirm.addEventListener('input', validatePasswords);
</script>