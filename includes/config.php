<?php

if (!defined('APP_PATH')) {
    define('APP_PATH', dirname(__DIR__));
}

if (!defined('PRODUCT_IMAGE_PATH')) {
    define('PRODUCT_IMAGE_PATH', APP_PATH . '/assets/images/products/');
}

if (!defined('PRODUCT_IMAGE_URL')) {
    define('PRODUCT_IMAGE_URL', '/assets/images/products/');
}

if (!defined('PRODUCT_PLACEHOLDER_URL')) {
    define('PRODUCT_PLACEHOLDER_URL', 'https://via.placeholder.com/300x300?text=No+Image');
}

class Database {
    private static $instance = null;
    private PDO $conn;

    private function __construct() {
        $env = parse_ini_file(__DIR__ . '/../.env');

        $dsn = "mysql:host={$env['DB_HOST']};dbname={$env['DB_NAME']};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        try {
            $this->conn = new PDO($dsn, $env['DB_USER'], $env['DB_PASS'], $options);
        } catch (PDOException $e) {
            die("❌ DB Connection failed: " . $e->getMessage());
        }
    }

    public static function getConnection(): PDO
    {
        if (!self::$instance) {
            self::$instance = new Database();
        }

        return self::$instance->conn;
    }

}
function loadRepo($relativePath): void
{
    require_once APP_PATH . '/' . ltrim($relativePath, '/');
}
