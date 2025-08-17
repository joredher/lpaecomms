<?php

namespace Lpaecomms;

use PDO;
use PDOException;

class Database
{
    private static ?self $instance = null;
    private PDO $conn;

    private function __construct()
    {
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
            self::$instance = new self();
        }

        return self::$instance->conn;
    }
}

