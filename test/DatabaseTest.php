<?php

/**
 * Database loader
 */
class DatabaseTest
{

    public static $connection;

    public static function setUp() {
        // Establish connection
        include('database.config.php');
        self::$connection = new PDO("mysql:host={$db['host']};dbname={$db['name']}", $db['user'], $db['password']);
        self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // Create test db
        self::$connection->query("CREATE DATABASE IF NOT EXISTS {$db['name']}");

        // Create test table
        $query = 'CREATE TABLE IF NOT EXISTS users (
            id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255),
            email VARCHAR(255),
            birthdate DATETIME,
            sex CHAR(1)
        )';
        self::$connection->query($query);
    }

    public static function clear() {
        self::$connection->query("TRUNCATE TABLE users");

    }
}
