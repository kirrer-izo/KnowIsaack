<?php

namespace App\Infrastructure\Database;

// Use a conditional require to prevent path issues during testing
if (file_exists(__DIR__ . '/../../config/config.php')) {
    require_once __DIR__ . '/../../config/config.php';
}

use PDO;
use PDOException;

class DatabaseConnection {
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        // 1. Get values from environment (PHPUnit) or fallback to constants (App)
        $host = getenv('DB_HOST') ?: (defined('DB_HOST') ? \DB_HOST : 'localhost');
        $port = getenv('DB_PORT') ?: '5432';
        $db   = getenv('DB_DATABASE_TEST') ?: (defined('DB_NAME') ? \DB_NAME : '');
        $user = getenv('DB_USER') ?: (defined('DB_USER') ? \DB_USER : '');
        $pass = getenv('DB_PASSWORD') ?: (defined('DB_PASSWORD') ? \DB_PASSWORD : '');

        $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";

        // 2. Add port= to the DSN!
        $dsn = "pgsql:host=$host;port=$port;dbname=$db";

        try {
            $this->pdo = new PDO($dsn, $user, $pass);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // If we are in CLI (Testing), don't die(), just throw it
            if (PHP_SAPI === 'cli') {
                throw $e;
            }
            error_log($e->getMessage());
            die("A database error occurred.");
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function setExternalConnection(PDO $pdo): void
    {
        $this->pdo = $pdo;
    }

    public function getConnection()
    {
        return $this->pdo;
    }
}