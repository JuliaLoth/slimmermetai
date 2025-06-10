<?php

/**
 * Legacy Database Bridge voor backward compatibility
 * Deze klasse biedt legacy database toegang via global $pdo
 */

if (!defined('LEGACY_DB_BRIDGE_LOADED')) {
    define('LEGACY_DB_BRIDGE_LOADED', true);

    // Global PDO variabele voor legacy compatibility
    global $pdo;
    
    if (!isset($pdo) || $pdo === null) {
        // Probeer database connectie te maken
        try {
            $config = [
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'dbname' => $_ENV['DB_NAME'] ?? 'slimmermetai_test',
                'username' => $_ENV['DB_USER'] ?? 'root',
                'password' => $_ENV['DB_PASS'] ?? '',
                'charset' => 'utf8mb4'
            ];
            
            $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
            
            $pdo = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            
        } catch (PDOException $e) {
            // Voor tests gebruiken we een in-memory SQLite database
            try {
                $pdo = new PDO('sqlite::memory:', null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
                
                // Basis test tabellen aanmaken
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS users (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        name VARCHAR(255) NOT NULL,
                        email VARCHAR(255) NOT NULL UNIQUE,
                        password VARCHAR(255) NOT NULL,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                    )
                ");
                
            } catch (PDOException $sqliteError) {
                throw new Exception('Database verbindingsfout. Probeer later opnieuw.');
            }
        }
    }
    
    /**
     * Legacy functie om PDO instance op te halen
     */
    function getLegacyDatabase(): PDO
    {
        global $pdo;
        return $pdo;
    }
    
    /**
     * Legacy functie voor database query
     */
    function legacyQuery(string $sql, array $params = []): PDOStatement
    {
        global $pdo;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    /**
     * Legacy functie voor database fetch
     */
    function legacyFetch(string $sql, array $params = []): ?array
    {
        $stmt = legacyQuery($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }
} 