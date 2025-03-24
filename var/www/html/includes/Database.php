<?php
/**
 * Database Class
 * Zorgt voor database connecties en queries
 */
class Database {
    private $conn;
    private static $instance = null;
    
    /**
     * Private constructor voor Singleton pattern
     */
    private function __construct() {
        try {
            require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/config.php';
            
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            $this->logError($e->getMessage());
            die("Database connectie mislukt: " . (DEBUG_MODE ? $e->getMessage() : "Neem contact op met de administrator."));
        }
    }
    
    /**
     * Get database instance (Singleton pattern)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Voer een query uit met prepared statements
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->logError($e->getMessage() . " - SQL: " . $sql);
            if (DEBUG_MODE) {
                throw new Exception("Database query error: " . $e->getMessage());
            }
            return false;
        }
    }
    
    /**
     * Haal een enkele rij op
     */
    public function getRow($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetch() : false;
    }
    
    /**
     * Haal meerdere rijen op
     */
    public function getRows($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetchAll() : false;
    }
    
    /**
     * Insert een record en retourneer het laatst ingevoegde ID
     */
    public function insert($table, $data) {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO {$table} (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
                
        if ($this->query($sql, array_values($data))) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Update records
     */
    public function update($table, $data, $where, $whereParams = []) {
        $fields = array_keys($data);
        $setPlaceholders = array_map(function($field) {
            return "{$field} = ?";
        }, $fields);
        
        $sql = "UPDATE {$table} SET " . implode(', ', $setPlaceholders) . " WHERE {$where}";
        $params = array_merge(array_values($data), $whereParams);
        
        return $this->query($sql, $params) ? true : false;
    }
    
    /**
     * Verwijder records
     */
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return $this->query($sql, $params) ? true : false;
    }
    
    /**
     * Begin een transactie
     */
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }
    
    /**
     * Commit een transactie
     */
    public function commit() {
        return $this->conn->commit();
    }
    
    /**
     * Rollback een transactie
     */
    public function rollback() {
        return $this->conn->rollBack();
    }
    
    /**
     * Log database fouten
     */
    private function logError($message) {
        if (defined('LOG_PATH')) {
            $logFile = LOG_PATH . 'db_errors.log';
            $timestamp = date('Y-m-d H:i:s');
            $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;
            
            // Maak de log map aan als deze niet bestaat
            if (!is_dir(LOG_PATH)) {
                mkdir(LOG_PATH, 0755, true);
            }
            
            // Schrijf naar het log bestand
            file_put_contents($logFile, $logMessage, FILE_APPEND);
        }
    }
}
?> 