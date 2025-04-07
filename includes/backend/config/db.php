<?php
/**
 * Database configuratie bestand voor MySQL (gebruikt met Antagonist hosting)
 * Aangepast voor het gebruik van meerdere databases
 */

// Haal environment variabelen op
$envFile = dirname(dirname(__FILE__)) . '/.env';
$env = [];

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        // Verwijder quotes indien aanwezig
        if (strpos($value, '"') === 0 || strpos($value, "'") === 0) {
            $value = substr($value, 1, -1);
        }
        
        $env[$name] = $value;
    }
}

// Functie om environment variabelen op te halen met fallback
function getEnv($key, $default = null) {
    global $env;
    
    if (isset($env[$key])) {
        return $env[$key];
    }
    
    $value = getenv($key);
    if ($value !== false) {
        return $value;
    }
    
    return $default;
}

// Database configuratie voor users database
$usersDbConfig = [
    'host' => getEnv('DB_HOST', 'localhost'),
    'dbname' => getEnv('DB_NAME', 'slimmermetai'),
    'user' => getEnv('DB_USER', 'root'),
    'password' => getEnv('DB_PASSWORD', ''),
    'port' => getEnv('DB_PORT', 3306),
    'charset' => 'utf8mb4'
];

// Database configuratie voor sessions database
$sessionsDbConfig = [
    'host' => getEnv('SESSIONS_DB_HOST', 'localhost'),
    'dbname' => getEnv('SESSIONS_DB_NAME', 'slimmermetai_sessions'),
    'user' => getEnv('SESSIONS_DB_USER', 'root'),
    'password' => getEnv('SESSIONS_DB_PASSWORD', ''),
    'port' => getEnv('SESSIONS_DB_PORT', 3306),
    'charset' => 'utf8mb4'
];

// Database configuratie voor login attempts database
$loginAttemptsDbConfig = [
    'host' => getEnv('LOGIN_ATTEMPTS_DB_HOST', 'localhost'),
    'dbname' => getEnv('LOGIN_ATTEMPTS_DB_NAME', 'slimmermetai_login_attempts'),
    'user' => getEnv('LOGIN_ATTEMPTS_DB_USER', 'root'),
    'password' => getEnv('LOGIN_ATTEMPTS_DB_PASSWORD', ''),
    'port' => getEnv('LOGIN_ATTEMPTS_DB_PORT', 3306),
    'charset' => 'utf8mb4'
];

/**
 * Users database verbinding opzetten
 * 
 * @return PDO De PDO database connectie
 */
function getUsersDb() {
    global $usersDbConfig;
    static $usersDb = null;
    
    if ($usersDb === null) {
        try {
            $dsn = "mysql:host={$usersDbConfig['host']};dbname={$usersDbConfig['dbname']};charset={$usersDbConfig['charset']}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $usersDb = new PDO($dsn, $usersDbConfig['user'], $usersDbConfig['password'], $options);
        } catch (PDOException $e) {
            error_log('Users database connectie mislukt: ' . $e->getMessage());
            throw $e;
        }
    }
    
    return $usersDb;
}

/**
 * Sessions database verbinding opzetten
 * 
 * @return PDO De PDO database connectie
 */
function getSessionsDb() {
    global $sessionsDbConfig;
    static $sessionsDb = null;
    
    if ($sessionsDb === null) {
        try {
            $dsn = "mysql:host={$sessionsDbConfig['host']};dbname={$sessionsDbConfig['dbname']};charset={$sessionsDbConfig['charset']}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $sessionsDb = new PDO($dsn, $sessionsDbConfig['user'], $sessionsDbConfig['password'], $options);
        } catch (PDOException $e) {
            error_log('Sessions database connectie mislukt: ' . $e->getMessage());
            throw $e;
        }
    }
    
    return $sessionsDb;
}

/**
 * Login attempts database verbinding opzetten
 * 
 * @return PDO De PDO database connectie
 */
function getLoginAttemptsDb() {
    global $loginAttemptsDbConfig;
    static $loginAttemptsDb = null;
    
    if ($loginAttemptsDb === null) {
        try {
            $dsn = "mysql:host={$loginAttemptsDbConfig['host']};dbname={$loginAttemptsDbConfig['dbname']};charset={$loginAttemptsDbConfig['charset']}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $loginAttemptsDb = new PDO($dsn, $loginAttemptsDbConfig['user'], $loginAttemptsDbConfig['password'], $options);
        } catch (PDOException $e) {
            error_log('Login attempts database connectie mislukt: ' . $e->getMessage());
            throw $e;
        }
    }
    
    return $loginAttemptsDb;
}

/**
 * Test alle database connecties
 * 
 * @return bool True als alle connecties werken, anders false
 */
function testAllDbConnections() {
    try {
        getUsersDb()->query('SELECT 1');
        getSessionsDb()->query('SELECT 1');
        getLoginAttemptsDb()->query('SELECT 1');
        return true;
    } catch (PDOException $e) {
        error_log('Database connectie test mislukt: ' . $e->getMessage());
        return false;
    }
}

/**
 * Query uitvoeren op users database
 * 
 * @param string $sql SQL query
 * @param array $params Query parameters
 * @return array Resultaten van de query
 */
function queryUsers($sql, $params = []) {
    try {
        $stmt = getUsersDb()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Users database query fout: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Query uitvoeren op sessions database
 * 
 * @param string $sql SQL query
 * @param array $params Query parameters
 * @return array Resultaten van de query
 */
function querySessions($sql, $params = []) {
    try {
        $stmt = getSessionsDb()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Sessions database query fout: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Query uitvoeren op login attempts database
 * 
 * @param string $sql SQL query
 * @param array $params Query parameters
 * @return array Resultaten van de query
 */
function queryLoginAttempts($sql, $params = []) {
    try {
        $stmt = getLoginAttemptsDb()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Login attempts database query fout: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Enkele rij ophalen uit users database
 * 
 * @param string $sql SQL query
 * @param array $params Query parameters
 * @return array|null Enkele rij of null als niets gevonden
 */
function getUserRow($sql, $params = []) {
    try {
        $stmt = getUsersDb()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    } catch (PDOException $e) {
        error_log('Users database getUserRow fout: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Enkele rij ophalen uit sessions database
 * 
 * @param string $sql SQL query
 * @param array $params Query parameters
 * @return array|null Enkele rij of null als niets gevonden
 */
function getSessionRow($sql, $params = []) {
    try {
        $stmt = getSessionsDb()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    } catch (PDOException $e) {
        error_log('Sessions database getSessionRow fout: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Enkele rij ophalen uit login attempts database
 * 
 * @param string $sql SQL query
 * @param array $params Query parameters
 * @return array|null Enkele rij of null als niets gevonden
 */
function getLoginAttemptRow($sql, $params = []) {
    try {
        $stmt = getLoginAttemptsDb()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    } catch (PDOException $e) {
        error_log('Login attempts database getLoginAttemptRow fout: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Insert query uitvoeren op users database en ID ophalen
 * 
 * @param string $sql SQL query
 * @param array $params Query parameters
 * @return int ID van de ingevoegde rij
 */
function insertUserId($sql, $params = []) {
    try {
        $db = getUsersDb();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $db->lastInsertId();
    } catch (PDOException $e) {
        error_log('Users database insertUserId fout: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Insert query uitvoeren op sessions database en ID ophalen
 * 
 * @param string $sql SQL query
 * @param array $params Query parameters
 * @return int ID van de ingevoegde rij
 */
function insertSessionId($sql, $params = []) {
    try {
        $db = getSessionsDb();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $db->lastInsertId();
    } catch (PDOException $e) {
        error_log('Sessions database insertSessionId fout: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Insert query uitvoeren op login attempts database en ID ophalen
 * 
 * @param string $sql SQL query
 * @param array $params Query parameters
 * @return int ID van de ingevoegde rij
 */
function insertLoginAttemptId($sql, $params = []) {
    try {
        $db = getLoginAttemptsDb();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $db->lastInsertId();
    } catch (PDOException $e) {
        error_log('Login attempts database insertLoginAttemptId fout: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Transactie uitvoeren op users database
 * 
 * @param callable $callback Functie die de transactie uitvoert
 * @return mixed Resultaat van de callback functie
 */
function usersTransaction($callback) {
    $db = getUsersDb();
    
    try {
        $db->beginTransaction();
        $result = $callback($db);
        $db->commit();
        return $result;
    } catch (Exception $e) {
        $db->rollBack();
        error_log('Users database transactie fout: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Transactie uitvoeren op sessions database
 * 
 * @param callable $callback Functie die de transactie uitvoert
 * @return mixed Resultaat van de callback functie
 */
function sessionsTransaction($callback) {
    $db = getSessionsDb();
    
    try {
        $db->beginTransaction();
        $result = $callback($db);
        $db->commit();
        return $result;
    } catch (Exception $e) {
        $db->rollBack();
        error_log('Sessions database transactie fout: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Transactie uitvoeren op login attempts database
 * 
 * @param callable $callback Functie die de transactie uitvoert
 * @return mixed Resultaat van de callback functie
 */
function loginAttemptsTransaction($callback) {
    $db = getLoginAttemptsDb();
    
    try {
        $db->beginTransaction();
        $result = $callback($db);
        $db->commit();
        return $result;
    } catch (Exception $e) {
        $db->rollBack();
        error_log('Login attempts database transactie fout: ' . $e->getMessage());
        throw $e;
    }
}
?> 