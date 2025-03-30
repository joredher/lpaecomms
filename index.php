<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Get the requested page, default to home
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Security: Allow only specific pages
$allowed_pages = ['home', 'login', 'product'];
if (!in_array($page, $allowed_pages)) {
    $page = 'home';
}
?>

<!doctype html>
<html lang="en">
<body>
    <div id="main">
        <?php include 'includes/header.php'?>
        <?php include 'includes/brands.php'?>
        <div class="content">
            <?php include 'pages/'.$page.".php"?>
        </div>
        <?php include 'includes/footer.php'?>
    </div>
<!--    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>-->
</body>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>LPA Ecomms</title>
<!--    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">-->
    <link rel="stylesheet" href="assets/css/ecomms_style.css">
    <link rel="stylesheet" href="assets/css/normalize.css">
</head>
</html>
