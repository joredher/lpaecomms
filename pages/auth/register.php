<?php
echo "<!-- ENTERED register.php -->";
$path = "./../../assets/images/auth/";

?>

<div class="auth-page-container">
    <div class="form-wrapper">
        <div class="logo-container text-center mb-4">
            <img src="./../../assets/images/Logo.svg" alt="Ecommerce Logo" class="logo-img">
        </div>
        <div class="sign-up">
            <form method="post" action="/auth.register" >
                <div class="form-group position-relative mb-2">
                    <label>
                        <img class="input-icon" src="<?= $path ?>user.svg" alt="name">
                        <input class="form-control ps-5" type="text" name="firstname" placeholder="Name" required>
                    </label>
                </div>
                <div class="form-group position-relative mb-2">
                    <label>
                        <img class="input-icon" src="<?= $path ?>user.svg" alt="lastname">
                        <input class="form-control ps-5" type="text" name="lastname" placeholder="Lastname" required>
                    </label>
                </div>
                <div class="form-group position-relative mb-2">
                    <label>
                        <img class="input-icon" src="<?= $path ?>email.svg" alt="email">
                        <input class="form-control ps-5" type="email" name="email" placeholder="Email" required>
                    </label>
                </div>
                <div class="form-group position-relative mb-2">
                    <label>
                        <img class="input-icon" src="<?= $path ?>phone.svg" alt="phone">
                        <input class="form-control ps-5" type="number" name="phone" placeholder="Phone" required>
                    </label>
                </div>
                <div class="form-group position-relative mb-2">
                    <label>
                        <img class="input-icon" src="<?= $path ?>password_key.svg" alt="password">
                        <input class="form-control ps-5" type="password" name="password" placeholder="Password" required>
                    </label>
                </div>

                <button type="submit" class="submit-btn">SIGN UP</button>
            </form>
            <a class="sign-up-link" href="/login">Log In</a>
        </div>

    </div>
</div>
