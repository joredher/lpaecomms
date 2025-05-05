<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Get the requested page, default to home
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Security: Allow only specific pages
$allowed_pages = ['home', 'login', 'product', 'categories', 'about', 'contact'];
if (!in_array($page, $allowed_pages)) {
    $page = 'home';
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>LPA Ecomms</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/ecomms_style.css">
    <link rel="stylesheet" href="assets/css/bootstrap_customize.css">
    <link rel="stylesheet" href="assets/css/product_detail.css">
    <link rel="stylesheet" href="assets/css/normalize.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
          rel="stylesheet">
</head>
<body>
<div id="main">
    <?php include 'includes/header.php' ?>
    <?php include 'includes/brands.php' ?>
    <div class="content">
        <?php include 'pages/' . $page . ".php" ?>
    </div>
    <?php include 'includes/footer.php' ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
</body>
</html>
