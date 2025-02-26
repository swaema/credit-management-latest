<?php
class Database {
    private static $connection;

    public static function getConnection() {
        if (!self::$connection) {
            self::$connection = new mysqli('localhost', 'safefund_swaema', 'Dissertation27$', 'safefund_credit_management');
            if (self::$connection->connect_error) {
                die('Database connection failed: ' . self::$connection->connect_error);
            }
        }
        return self::$connection;
    }
}
