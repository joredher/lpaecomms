<?php
echo "<!-- ENTERED register.php -->";
$path = "assets/images/auth/";

?>

<div class="auth-page-container">
    <div class="form-wrapper wrapper-login">
        <div class="logo-container text-center mb-4">
            <img src="assets/images/Logo.svg" alt="Ecommerce Logo" class="logo-img">
        </div>
        <div class="login">
            <form method="post" action="auth.forgot" class="m-0 position-relative">
                <div class="form-group position-relative mb-2">
                    <label>
                        <img class="input-icon" src="<?= $path ?>user.svg" alt="email">
                        <input class="form-control ps-5" type="email" name="email" placeholder="Email" required
                               title="Please, fill the field with your Email.">
                    </label>
                </div>
                <button type="submit" class="submit-btn d-none" id="recover-btn">
                    <span>Recover</span>
                </button>
            </form>
            <a class="sign-up-link text-decoration-none text-light-emphasis" href="login">Return to Log In</a>
        </div>
    </div>
</div>

<script>
    const input = document.querySelector('input[name="email"]');
    const button = document.getElementById('recover-btn');

    input.addEventListener('input', function () {
        if (this.value.trim().length > 0) {
            button.classList.remove('d-none');
        } else {
            button.classList.add('d-none');
        }
    });
</script>