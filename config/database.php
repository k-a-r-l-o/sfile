<?php

date_default_timezone_set('Asia/Manila');
require_once __DIR__ . '/config.php';

class database {
    private static $connectionString = null;
    private static $connection = null;

    public function __construct() {
        self::$connectionString = "mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME . ";charset=utf8";
    }

    public function openConnection() {
        if (self::$connection === null) {
            try {
                self::$connection = new PDO(self::$connectionString, DB_USER, DB_PASS);
                self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                error_log('Connection to MySQL opened successfully.');
            } catch (PDOException $e) {
                error_log('Connection to MySQL failed: ' . $e->getMessage());
            }
        }
    }

    public function closeConnection() {
        if (self::$connection !== null) {
            self::$connection = null;
            error_log('Connection to MySQL closed.');
        }
    }

    public function getConnection() {
        $this->openConnection();
        return self::$connection;
    }
}

?>
