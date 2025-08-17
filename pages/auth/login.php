<?php
// Path for auth-related images
$path = "assets/images/auth/";

$hasValidateThroughFirst = isset($_SESSION['pending_user_id']);
?>

<div class="auth-page-container">
    <div class="form-wrapper wrapper-login">
        <div class="logo-container text-center mb-4">
            <img src="assets/images/Logo.svg" alt="Ecommerce Logo" class="logo-img">
        </div>
        <div class="login">
            <?php if ($hasValidateThroughFirst): ?>
                <form method="post" action="verify-code" class="m-0 position-relative">
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
                <form method="post" action="auth.login" class="m-0 position-relative">
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
                            <input class="form-control ps-5" type="password" name="password" placeholder="Password"
                                   required>
                        </label>
                    </div>

                    <button type="submit" class="submit-btn">Login</button>
                </form>
            <?php endif; ?>
            <a class="sign-up-link" href="register">SIGN UP</a>
            <a class="sign-up-link h6" href="forgot_password">Forgot Password</a>
        </div>

    </div>
</div>