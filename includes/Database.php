<?php
/**
 * Database Connection Class
 * Handles all MySQL database connections with error handling
 */

class Database {
    private $host = '127.0.0.1';
    private $db_name = 'ghg_database';
    private $username = 'root';
    private $password = '';
    private $port = 3306;
    private $conn;

    /**
     * Connect to database
     */
    public function connect() {
        $this->conn = null;

        try {
            // Create connection
            $this->conn = new mysqli(
                $this->host,
                $this->username,
                $this->password,
                $this->db_name,
                $this->port
            );

            // Check connection
            if ($this->conn->connect_error) {
                throw new Exception("Connection Failed: " . $this->conn->connect_error);
            }

            // Set charset to UTF-8
            $this->conn->set_charset("utf8mb4");

            return $this->conn;

        } catch (Exception $e) {
            // Log error
            error_log("Database Connection Error: " . $e->getMessage());
            die("Database Connection Error: Unable to connect to database. Please contact administrator.");
        }
    }

    /**
     * Get connection
     */
    public function getConnection() {
        if ($this->conn === null) {
            $this->connect();
        }
        return $this->conn;
    }

    /**
     * Execute query
     */
    public function query($sql) {
        try {
            $result = $this->conn->query($sql);
            if (!$result) {
                throw new Exception("Query Error: " . $this->conn->error . " SQL: " . $sql);
            }
            return $result;
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Execute prepared statement
     */
    public function prepare($sql) {
        try {
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare Error: " . $this->conn->error);
            }
            return $stmt;
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Close connection
     */
    public function close() {
        if ($this->conn) {
            $this->conn->close();
        }
    }

    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->conn->insert_id;
    }

    /**
     * Get affected rows
     */
    public function affectedRows() {
        return $this->conn->affected_rows;
    }
}
?>
