<?php
/**
 * Database Class
 * 
 * Biedt een veilige en consistente interface voor databaseoperaties.
 * Implementeert PDO voor databaseverbindingen en prepared statements.
 * 
 * @version 1.0.0
 * @author SlimmerMetAI Team
 */

class Database {
    private static $instance = null;
    private $pdo = null;
    private $isConnected = false;
    private $transactionCounter = 0;
    
    /**
     * Private constructor voor Singleton pattern
     */
    private function __construct() {
        // Laad de configuratie
        require_once dirname(dirname(__FILE__)) . '/config/Config.php';
        $config = Config::getInstance();
    }
    
    /**
     * Singleton pattern implementatie
     * 
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    /**
     * Maak verbinding met de database
     * 
     * @throws PDOException Als de verbinding mislukt
     * @return boolean True als de verbinding is gemaakt
     */
    public function connect() {
        if ($this->isConnected) {
            return true;
        }
        
        try {
            // Laad de configuratie
            $config = Config::getInstance();
            
            $host = $config->get('db_host');
            $name = $config->get('db_name');
            $user = $config->get('db_user');
            $pass = $config->get('db_pass');
            $charset = $config->get('db_charset');
            
            $dsn = "mysql:host=$host;dbname=$name;charset=$charset";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $charset"
            ];
            
            $this->pdo = new PDO($dsn, $user, $pass, $options);
            $this->isConnected = true;
            
            return true;
        } catch (PDOException $e) {
            // Laad de ErrorHandler
            require_once dirname(dirname(__FILE__)) . '/utils/ErrorHandler.php';
            $errorHandler = ErrorHandler::getInstance();
            $errorHandler->logError("Database connectie mislukt", ['error' => $e->getMessage()]);
            
            // Gooi de fout door, maar zonder gevoelige details in productie
            if ($config->get('debug_mode')) {
                throw $e;
            } else {
                throw new PDOException("Database verbindingsfout. Probeer het later opnieuw.");
            }
        }
    }
    
    /**
     * Sluit de database verbinding
     */
    public function disconnect() {
        $this->pdo = null;
        $this->isConnected = false;
    }
    
    /**
     * Start een database transactie
     * 
     * @return boolean True als de transactie is gestart
     */
    public function beginTransaction() {
        if (!$this->isConnected) {
            $this->connect();
        }
        
        if (!$this->transactionCounter) {
            $this->pdo->beginTransaction();
        }
        
        $this->transactionCounter++;
        
        return true;
    }
    
    /**
     * Commit de huidige transactie
     * 
     * @return boolean True als de transactie is gecommit
     */
    public function commit() {
        if (!$this->isConnected) {
            return false;
        }
        
        $this->transactionCounter--;
        
        if (!$this->transactionCounter) {
            $this->pdo->commit();
        }
        
        return true;
    }
    
    /**
     * Rollback de huidige transactie
     * 
     * @return boolean True als de transactie is teruggedraaid
     */
    public function rollback() {
        if (!$this->isConnected) {
            return false;
        }
        
        $this->transactionCounter = 0;
        
        return $this->pdo->rollBack();
    }
    
    /**
     * Voer een query uit
     * 
     * @param string $sql SQL query met placeholders
     * @param array $params Parameters voor de query
     * @return PDOStatement Het statement object
     */
    public function query($sql, $params = []) {
        if (!$this->isConnected) {
            $this->connect();
        }
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            // Laad de ErrorHandler
            require_once dirname(dirname(__FILE__)) . '/utils/ErrorHandler.php';
            $errorHandler = ErrorHandler::getInstance();
            $errorHandler->logError("Database query mislukt", [
                'query' => $sql,
                'params' => json_encode($params),
                'error' => $e->getMessage()
            ]);
            
            // Log de fout, maar gooi hem door
            throw $e;
        }
    }
    
    /**
     * Haal één rij op
     * 
     * @param string $sql SQL query met placeholders
     * @param array $params Parameters voor de query
     * @return array|null De opgehaalde rij of null
     */
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Haal alle rijen op
     * 
     * @param string $sql SQL query met placeholders
     * @param array $params Parameters voor de query
     * @return array De opgehaalde rijen
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Voeg een rij toe aan een tabel
     * 
     * @param string $table De tabelnaam
     * @param array $data Associatieve array met kolomnamen en waarden
     * @return string|int Het ID van de toegevoegde rij
     */
    public function insert($table, $data) {
        if (!$this->isConnected) {
            $this->connect();
        }
        
        try {
            $fields = array_keys($data);
            $placeholders = array_fill(0, count($fields), '?');
            
            $sql = "INSERT INTO $table (" . implode(', ', $fields) . ") 
                    VALUES (" . implode(', ', $placeholders) . ")";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(array_values($data));
            
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            // Laad de ErrorHandler
            require_once dirname(dirname(__FILE__)) . '/utils/ErrorHandler.php';
            $errorHandler = ErrorHandler::getInstance();
            $errorHandler->logError("Database insert mislukt", [
                'table' => $table,
                'data' => json_encode($data),
                'error' => $e->getMessage()
            ]);
            
            // Log de fout, maar gooi hem door
            throw $e;
        }
    }
    
    /**
     * Update rijen in een tabel
     * 
     * @param string $table De tabelnaam
     * @param array $data Associatieve array met kolomnamen en waarden om te updaten
     * @param string $where WHERE clausule
     * @param array $whereParams Parameters voor de WHERE clausule
     * @return int Het aantal beïnvloede rijen
     */
    public function update($table, $data, $where, $whereParams = []) {
        if (!$this->isConnected) {
            $this->connect();
        }
        
        try {
            $set = array_map(function($field) {
                return "$field = ?";
            }, array_keys($data));
            
            $sql = "UPDATE $table SET " . implode(', ', $set) . " WHERE $where";
            
            $stmt = $this->pdo->prepare($sql);
            $params = array_merge(array_values($data), $whereParams);
            $stmt->execute($params);
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            // Laad de ErrorHandler
            require_once dirname(dirname(__FILE__)) . '/utils/ErrorHandler.php';
            $errorHandler = ErrorHandler::getInstance();
            $errorHandler->logError("Database update mislukt", [
                'table' => $table,
                'data' => json_encode($data),
                'where' => $where,
                'params' => json_encode($whereParams),
                'error' => $e->getMessage()
            ]);
            
            // Log de fout, maar gooi hem door
            throw $e;
        }
    }
    
    /**
     * Verwijder rijen uit een tabel
     * 
     * @param string $table De tabelnaam
     * @param string $where WHERE clausule
     * @param array $params Parameters voor de WHERE clausule
     * @return int Het aantal beïnvloede rijen
     */
    public function delete($table, $where, $params = []) {
        if (!$this->isConnected) {
            $this->connect();
        }
        
        try {
            $sql = "DELETE FROM $table WHERE $where";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            // Laad de ErrorHandler
            require_once dirname(dirname(__FILE__)) . '/utils/ErrorHandler.php';
            $errorHandler = ErrorHandler::getInstance();
            $errorHandler->logError("Database delete mislukt", [
                'table' => $table,
                'where' => $where,
                'params' => json_encode($params),
                'error' => $e->getMessage()
            ]);
            
            // Log de fout, maar gooi hem door
            throw $e;
        }
    }
    
    /**
     * Controleer of een record bestaat
     * 
     * @param string $table De tabelnaam
     * @param string $where WHERE clausule
     * @param array $params Parameters voor de WHERE clausule
     * @return boolean True als het record bestaat, anders false
     */
    public function exists($table, $where, $params = []) {
        $sql = "SELECT EXISTS(SELECT 1 FROM $table WHERE $where LIMIT 1) as `exists`";
        $result = $this->fetch($sql, $params);
        return (bool) $result['exists'];
    }
    
    /**
     * Tel het aantal records in een tabel
     * 
     * @param string $table De tabelnaam
     * @param string $where WHERE clausule (optioneel)
     * @param array $params Parameters voor de WHERE clausule (optioneel)
     * @return int Het aantal records
     */
    public function count($table, $where = '', $params = []) {
        $sql = "SELECT COUNT(*) as `count` FROM $table";
        
        if ($where) {
            $sql .= " WHERE $where";
        }
        
        $result = $this->fetch($sql, $params);
        return (int) $result['count'];
    }
    
    /**
     * Geef het PDO object terug voor geavanceerde operaties
     * 
     * @return PDO Het PDO object
     */
    public function getPdo() {
        if (!$this->isConnected) {
            $this->connect();
        }
        
        return $this->pdo;
    }
    
    /**
     * Ontsnapt veldnamen voor veilig gebruik in queries
     * 
     * @param string $field Veldnaam om te ontsnappen
     * @return string Ontsnapte veldnaam
     */
    public function escapeField($field) {
        // We voorkomen SQL injectie door veldnamen te omringen met backticks
        return '`' . str_replace('`', '', $field) . '`';
    }
    
    /**
     * Helper functie om snel een enkele waarde uit de database te halen
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @param string $column Kolomnaam om op te halen
     * @return mixed De opgehaalde waarde
     */
    public function getValue($sql, $params = [], $column = null) {
        $result = $this->fetch($sql, $params);
        
        if (!$result) {
            return null;
        }
        
        if ($column !== null) {
            return isset($result[$column]) ? $result[$column] : null;
        }
        
        // Als geen kolom is opgegeven, geef de eerste waarde terug
        return reset($result);
    }
    
    /**
     * Helper functie om snel een kolom met waarden op te halen
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @param string $column Kolomnaam om op te halen
     * @return array Array met waarden
     */
    public function getColumn($sql, $params = [], $column = null) {
        $results = $this->fetchAll($sql, $params);
        $values = [];
        
        foreach ($results as $row) {
            if ($column !== null) {
                if (isset($row[$column])) {
                    $values[] = $row[$column];
                }
            } else {
                // Als geen kolom is opgegeven, neem de eerste waarde van elke rij
                $values[] = reset($row);
            }
        }
        
        return $values;
    }
}
