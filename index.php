<?php

session_start();
require_once 'includes/config.php';
require_once 'vendor/autoload.php'; // root-level
require_once 'middleware/SecurityHeadersMiddleware.php';
SecurityHeadersMiddleware::emit();
require_once 'includes/router.php';
