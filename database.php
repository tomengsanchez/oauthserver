<?php
// database.php

// Ensure this script is not accessed directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    die(header('HTTP/1.0 403 Forbidden'));
}

class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $conn;

    // Get the database connection
    public function connect() {
        $this->conn = null;

        try {
            $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->db_name;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // In a real application, you would log this error
            // For now, we'll output a generic error message
            header('HTTP/1.0 500 Internal Server Error');
            echo json_encode(['error' => 'Database connection error.']);
            exit();
        }

        return $this->conn;
    }
}