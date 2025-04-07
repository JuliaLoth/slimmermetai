<?php
/**
 * Debug script voor SlimmerMetAI.com
 * Dit script test de verbinding met de database en controleert de tabellen
 *
 * BELANGRIJK: Verwijder dit bestand na gebruik!
 */

// Voorkom directe toegang zonder geheime sleutel
$debug_key = isset($_GET['key']) ? $_GET['key'] : '';
if ($debug_key !== 'slimmermetai_debug_2023') {
    header('HTTP/1.1 403 Forbidden');
    echo 'Toegang geweigerd';
    exit;
}

// Outputbuffering inschakelen
ob_start();

// Definieer SITE_ROOT
define('SITE_ROOT', dirname(dirname(__FILE__)));

// Configuratie inladen
require_once __DIR__ . '/config.php';

echo "<h1>SlimmerMetAI.com Debug</h1>";
echo "<p>Deze pagina toont debug informatie voor het oplossen van problemen met het inloggen.</p>";

// PHP versie controleren
echo "<h2>PHP Informatie</h2>";
echo "<p>PHP versie: " . phpversion() . "</p>";
echo "<p>PDO beschikbaar: " . (extension_loaded('pdo') ? 'Ja' : 'Nee') . "</p>";
echo "<p>PDO MySQL beschikbaar: " . (extension_loaded('pdo_mysql') ? 'Ja' : 'Nee') . "</p>";

// Database connectie testen
echo "<h2>Database Connectie</h2>";
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo_test = new PDO($dsn, DB_USER, DB_PASS, $options);
    echo "<p style='color:green'>Database connectie succesvol!</p>";
    
    // Tabellen controleren
    echo "<h2>Database Tabellen</h2>";
    $tables = [
        'users' => false,
        'refresh_tokens' => false,
        'login_attempts' => false,
        'email_tokens' => false
    ];
    
    $stmt = $pdo_test->query("SHOW TABLES");
    $existing_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<ul>";
    foreach ($tables as $table => $exists) {
        $exists = in_array($table, $existing_tables);
        $tables[$table] = $exists;
        $color = $exists ? 'green' : 'red';
        echo "<li style='color:$color'>Tabel '$table': " . ($exists ? 'Aanwezig' : 'Ontbreekt') . "</li>";
    }
    echo "</ul>";
    
    // Als users tabel bestaat, kijk of er gebruikers zijn
    if ($tables['users']) {
        $stmt = $pdo_test->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch();
        echo "<p>Aantal gebruikers: " . $result['count'] . "</p>";
        
        // Test admin account
        $stmt = $pdo_test->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute(['admin@slimmermetai.com']);
        $admin = $stmt->fetch();
        
        if ($admin) {
            echo "<p style='color:green'>Admin account gevonden!</p>";
            echo "<p>Gebruikersnaam: " . htmlspecialchars($admin['name']) . "</p>";
            echo "<p>Email: " . htmlspecialchars($admin['email']) . "</p>";
            echo "<p>Rol: " . htmlspecialchars($admin['role']) . "</p>";
            
            // Wachtwoord testen
            $test_password = 'Admin123!';
            $password_correct = password_verify($test_password, $admin['password']);
            echo "<p>Wachtwoord 'Admin123!' is " . ($password_correct ? 'correct' : 'incorrect') . "</p>";
            
            if (!$password_correct) {
                echo "<p style='color:orange'>Het standaard wachtwoord lijkt niet te werken. Misschien is het gewijzigd of incorrent opgeslagen.</p>";
            }
        } else {
            echo "<p style='color:red'>Admin account niet gevonden!</p>";
            echo "<p>We moeten een admin account maken.</p>";
            
            // Optionele code om admin account aan te maken
            $create_admin = true; // Zet op true om admin aan te maken
            
            if ($create_admin) {
                $admin_name = 'Admin';
                $admin_email = 'admin@slimmermetai.com';
                $admin_password = 'Admin123!';
                $admin_password_hash = password_hash($admin_password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
                
                try {
                    $stmt = $pdo_test->prepare("INSERT INTO users (name, email, password, role, email_verified, created_at) VALUES (?, ?, ?, 'admin', 1, NOW())");
                    $result = $stmt->execute([$admin_name, $admin_email, $admin_password_hash]);
                    
                    if ($result) {
                        echo "<p style='color:green'>Admin account succesvol aangemaakt!</p>";
                    } else {
                        echo "<p style='color:red'>Kon geen admin account aanmaken.</p>";
                    }
                } catch (PDOException $e) {
                    echo "<p style='color:red'>Fout bij aanmaken admin: " . $e->getMessage() . "</p>";
                }
            }
        }
    }
    
    // Controleer of alle benodigde tabellen bestaan, zo niet, maak ze aan
    if (in_array(false, $tables)) {
        echo "<h2>Ontbrekende tabellen aanmaken</h2>";
        
        $create_tables = false; // Zet op true om tabellen aan te maken
        
        if ($create_tables) {
            // SQL voor ontbrekende tabellen
            $sql = [];
            
            if (!$tables['users']) {
                $sql[] = "CREATE TABLE users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    email VARCHAR(100) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    role ENUM('user', 'admin') DEFAULT 'user',
                    email_verified TINYINT(1) DEFAULT 0,
                    profile_picture VARCHAR(255) DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
                    last_login TIMESTAMP NULL,
                    INDEX idx_email (email)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            }
            
            if (!$tables['refresh_tokens']) {
                $sql[] = "CREATE TABLE refresh_tokens (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    token VARCHAR(255) NOT NULL UNIQUE,
                    expires_at TIMESTAMP NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    INDEX idx_token (token)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            }
            
            if (!$tables['login_attempts']) {
                $sql[] = "CREATE TABLE login_attempts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(100) NOT NULL,
                    ip_address VARCHAR(45) NOT NULL,
                    user_agent VARCHAR(255) NULL,
                    success TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_email (email),
                    INDEX idx_ip_address (ip_address)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            }
            
            if (!$tables['email_tokens']) {
                $sql[] = "CREATE TABLE email_tokens (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    token VARCHAR(255) NOT NULL UNIQUE,
                    type ENUM('verification', 'password_reset') NOT NULL,
                    expires_at TIMESTAMP NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    used_at TIMESTAMP NULL,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    INDEX idx_token (token)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            }
            
            // Voer SQL uit
            foreach ($sql as $query) {
                try {
                    $result = $pdo_test->exec($query);
                    echo "<p style='color:green'>SQL uitgevoerd: " . substr($query, 0, 50) . "...</p>";
                } catch (PDOException $e) {
                    echo "<p style='color:red'>SQL fout: " . $e->getMessage() . "</p>";
                }
            }
        } else {
            echo "<p>Schakel het aanmaken van tabellen in door \$create_tables = true te zetten.</p>";
        }
    }
    
    // Login helpers testen
    echo "<h2>Login Helpers Test</h2>";
    if (function_exists('generate_jwt')) {
        echo "<p style='color:green'>generate_jwt functie bestaat!</p>";
    } else {
        echo "<p style='color:red'>generate_jwt functie ontbreekt!</p>";
    }
    
    if (function_exists('generate_jwt_token')) {
        echo "<p style='color:green'>generate_jwt_token functie bestaat!</p>";
    } else {
        echo "<p style='color:red'>generate_jwt_token functie ontbreekt!</p>";
    }
    
    if (function_exists('validate_token')) {
        echo "<p style='color:green'>validate_token functie bestaat!</p>";
    } else {
        echo "<p style='color:red'>validate_token functie ontbreekt!</p>";
    }
    
    if (function_exists('sanitize_input')) {
        echo "<p style='color:green'>sanitize_input functie bestaat!</p>";
    } else {
        echo "<p style='color:red'>sanitize_input functie ontbreekt!</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color:red'>Database fout: " . $e->getMessage() . "</p>";
}

// Google API
echo "<h2>Google Inloggen</h2>";
echo "<p>Voor Google login, controleer de volgende punten:</p>";
echo "<ol>";
echo "<li>Controleer of je een geldig Google Client ID hebt geconfigureerd</li>";
echo "<li>Zorg dat het domein is goedgekeurd in de Google Developer Console</li>";
echo "<li>Controleer of de JavaScript SDK correct is geladen op de pagina</li>";
echo "<li>Zorg ervoor dat je redirect URL's correct zijn geconfigureerd</li>";
echo "</ol>";

// Buffer leegmaken en uitvoeren als HTML
header('Content-Type: text/html; charset=utf-8');
ob_end_flush();
?> 