<?php

namespace App\Infrastructure\Database;

use PDO;
use PDOException;

class MySQLConnection {
    private static $instance = null; // holds the MySQLConnection object
    private $pdo; // holds the PDO object inside it

     //   Private constructor — nobody can do new MySQLConnection()
    //    Only this class can create itself
    private function __construct()
    {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $this->pdo = new PDO($dsn, DB_USER, DB_PASSWORD);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            die("A database error occurred. Please try again later.");
        }
    }

    //    The only way to get the connection
    //    If one exists, return it
    //    If not, create it first then return it
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

     // A method to hand the PDO to whoever needs it
    public function getConnection()
    {
        return $this->pdo;
    }
}