<?php
$path = "./../../assets/images/auth/";
$title = $title ?? 'Admin Login';
?>

<div class="auth-page-container">
    <div class="form-wrapper wrapper-login">
        <div class="logo-container text-center mb-4">
            <img src="./../../assets/images/Logo.svg" alt="Ecommerce Logo" class="logo-img">
        </div>
        <div class="login">
            <form method="post" action="/auth.adminLogin" class="m-0 position-relative">
                <div class="form-group position-relative mb-2">
                    <label>
                        <img class="input-icon" src="<?= $path ?>user.svg" alt="username">
                        <input class="form-control ps-5" type="text" name="username" placeholder="Admin Email" required
                               title="Please, fill the field with your admin email.">
                    </label>
                </div>
                <div class="form-group position-relative">
                    <label>
                        <img class="input-icon" src="<?= $path ?>password_key.svg" alt="password">
                        <input class="form-control ps-5" type="password" name="password" placeholder="Password" required>
                    </label>
                </div>
                <button type="submit" class="submit-btn">Login</button>
            </form>
        </div>
    </div>
</div>

