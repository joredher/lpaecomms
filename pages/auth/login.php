<?php
echo "<!-- ENTERED register.php -->";
$path = "./../../assets/images/auth/";

$hasValidateThroughFirst = isset($_SESSION['pending_user_id']);
?>

<div class="auth-page-container">
    <div class="form-wrapper wrapper-login">
        <div class="logo-container text-center mb-4">
            <img src="./../../assets/images/Logo.svg" alt="Ecommerce Logo" class="logo-img">
        </div>
        <div class="login">
            <?php if ($hasValidateThroughFirst): ?>
                <form method="post" action="/verify-code" class="m-0 position-relative">
                    <div class="form-group position-relative">
                        <label>
                            <img class="input-icon" src="<?= $path ?>password_key.svg" alt="Code">
                            <input class="form-control ps-5" type="number"
                                   minlength="100000"
                                   maxlength="999999"
                                   name="code_validation" placeholder="Validation Code"
                                   required>
                        </label>
                    </div>
                    <button type="submit" class="submit-btn">Login</button>
                </form>
            <?php else: ?>
                <form method="post" action="/auth.login" class="m-0 position-relative">
                    <div class="form-group position-relative mb-2">
                        <label>
                            <img class="input-icon" src="<?= $path ?>user.svg" alt="username">
                            <input class="form-control ps-5" type="text" name="username" placeholder="Username" required
                                   title="Please, fill the field with your username or email.">
                        </label>
                    </div>
                    <div class="form-group position-relative">
                        <label>
                            <img class="input-icon" src="<?= $path ?>password_key.svg" alt="password">
                            <input id="login_password" class="form-control ps-5 pe-5" type="password" name="password" placeholder="Password"
                                   autocomplete="current-password" required>
                        </label>
                        <button type="button" id="togglePasswordBtn" class="toggle-password-btn" aria-label="Show password" aria-pressed="false" title="Show password">
                            <!-- eye icon -->
                            <svg class="icon-eye" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            <!-- eye-off icon -->
                            <svg class="icon-eye-off d-none" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M17.94 17.94C16.24 19.01 14.23 19.67 12 19.67 5 19.67 1 12 1 12a21.35 21.35 0 0 1 5.11-5.92" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M10.59 10.59a2 2 0 0 0 2.82 2.82" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M23 12s-4 7-11 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M3 3l18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>

                    <button type="submit" class="submit-btn">Login</button>
                </form>
            <?php endif; ?>
            <a class="sign-up-link" href="/register">SIGN UP</a>
            <a class="sign-up-link h6" href="/forgot_password">Forgot Password</a>
        </div>

    </div>
</div>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var pwd = document.getElementById('login_password');
    var btn = document.getElementById('togglePasswordBtn');
    if (pwd && btn) {
      btn.addEventListener('click', function () {
        var show = pwd.type === 'password';
        pwd.type = show ? 'text' : 'password';
        btn.setAttribute('aria-pressed', String(show));
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        var eye = btn.querySelector('.icon-eye');
        var eyeOff = btn.querySelector('.icon-eye-off');
        if (eye && eyeOff) {
          eye.classList.toggle('d-none');
          eyeOff.classList.toggle('d-none');
        }
      });
    }
  });
</script>
