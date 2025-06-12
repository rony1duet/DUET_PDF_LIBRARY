<?php

/**
 * Database connection handler
 */

// Load configuration
require_once __DIR__ . '/../config/config.php';

class Database
{
    private static $instance = null;
    private $connection;

    /**
     * Constructor - establishes database connection
     */
    private function __construct()
    {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log error and display friendly message
            error_log('Database Connection Error: ' . $e->getMessage());
            die("Sorry, there was a problem connecting to the database. Please try again later.");
        }
    }

    /**
     * Get singleton instance
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get database connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Execute a query with parameters
     */
    public function query($sql, $params = [])
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log('Query Error: ' . $e->getMessage() . ' - SQL: ' . $sql);
            error_log('Parameters: ' . print_r($params, true));
            throw $e; // Re-throw to be handled by caller
        }
    }

    /**
     * Get a single row
     */
    public function getRow($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    /**
     * Get multiple rows
     */
    public function getRows($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Get a single value from first column
     */
    public function getValue($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchColumn();
    }

    /**
     * Fetch single column value (alias for getValue for backward compatibility)
     */
    public function fetchColumn($sql, $params = [])
    {
        return $this->getValue($sql, $params);
    }

    /**
     * Insert a record and return the ID
     */
    public function insert($table, $data)
    {
        $columns = array_keys($data);
        $placeholders = array_map(function ($item) {
            return ":$item";
        }, $columns);

        $sql = "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) "
            . "VALUES (" . implode(', ', $placeholders) . ")";

        $this->query($sql, $data);
        return $this->connection->lastInsertId();
    }

    /**
     * Update a record
     */
    public function update($table, $data, $where, $whereParams = [])
    {
        $setClauses = [];
        $params = [];

        foreach ($data as $column => $value) {
            $setClauses[] = "`$column` = :set_$column";
            $params["set_$column"] = $value;
        }

        $sql = "UPDATE `$table` SET " . implode(', ', $setClauses) . " WHERE $where";

        // Merge where parameters
        $params = array_merge($params, $whereParams);

        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Delete a record
     */
    public function delete($table, $where, $params = [])
    {
        $sql = "DELETE FROM `$table` WHERE $where";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Begin a transaction
     */
    public function beginTransaction()
    {
        return $this->connection->beginTransaction();
    }

    /**
     * Commit a transaction
     */
    public function commit()
    {
        return $this->connection->commit();
    }

    /**
     * Rollback a transaction
     */
    public function rollback()
    {
        return $this->connection->rollBack();
    }
}
