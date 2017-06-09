<?php

/**
 * Database loader
 */
class DatabaseLoader
{

    public static $connection;

    public static function setUp() {
        // Establish connection
        include('database.config.php');
        self::$connection = new PDO("mysql:host={$db['host']};dbname={$db['name']}", $db['user'], $db['password']);
        self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // Create test db
        self::$connection->query("CREATE DATABASE IF NOT EXISTS {$db['name']}");
    }

    public static function query($query) {
        // Run query
        self::$connection->query($query);
    }

    public static function clear($tableName) {
        self::$connection->query("TRUNCATE TABLE `$tableName`");
    }
}
