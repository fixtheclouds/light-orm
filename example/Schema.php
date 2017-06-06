<?php

/**
 * Schema loader for example
 */
class Schema
{
    /**
     * @var mysqli
     */
    protected $connection;

    /**
     * Schema loader constructor
     * @param mysqli $connection
     */
    public function __construct($connection) {
        $this->connection = $connection;
    }

    /**
     * Create database if not exists
     *
     * @param string $dbName
     * @throws Exception
     */
    public function createDatabase($dbName) {
        if (!$this->connection->query("CREATE DATABASE IF NOT EXISTS $dbName")) {
            throw new Exception('Error occurred while creating database');
        }
    }

    /**
     * Create users table if not exists
     * @param $tableName
     * @throws Exception
     */
    public function createUsersTable($tableName) {
        $query = "CREATE TABLE IF NOT EXISTS $tableName (
            id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255),
            email VARCHAR(255),
            birthdate DATETIME,
            sex CHAR(1)
        )";
        if (!$this->connection->query($query)) {
            throw new Exception('Error occurred while creating table');
        }
    }
}