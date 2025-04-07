<?php
/**
 * SlimmerMetAI.com Database Installatiescript
 * 
 * Dit script voert de database setup uit voor de SlimmerMetAI.com website.
 * Let op: Verwijder dit bestand na installatie om veiligheidsredenen!
 */

// Defineer SITE_ROOT als dat nog niet is gebeurd
if (!defined('SITE_ROOT')) {
    define('SITE_ROOT', __DIR__);
}

// Laad configuratie
require_once SITE_ROOT . '/api/config.php';

// Laad de SQL-installatiescript
$sqlScript = file_get_contents(dirname(SITE_ROOT) . '/database/setup.sql');

if (!$sqlScript) {
    die("Kan het SQL-script niet laden");
}

// Controleer of er een installatiesleutel is meegegeven
$installKey = isset($_GET['key']) ? $_GET['key'] : '';
$expectedKey = getenv('INSTALL_KEY') ?: 'slimmermetai_installatie_2023';

if ($installKey !== $expectedKey) {
    http_response_code(403);
    die("Ongeldige installatiesleutel");
}

// Valideer en bevestig vóór installatie
$confirmed = isset($_GET['confirm']) && $_GET['confirm'] === 'true';

if (!$confirmed) {
    echo '<html><head><title>SlimmerMetAI.com Database Installatie</title>';
    echo '<style>body{font-family:Arial,sans-serif;max-width:800px;margin:0 auto;padding:20px}
    h1{color:#2c3e50}
    .warning{background-color:#fcf8e3;border-left:5px solid #f0ad4e;padding:10px;margin:15px 0}
    .button{display:inline-block;padding:10px 20px;background-color:#3498db;color:white;text-decoration:none;border-radius:4px;margin-top:20px}
    .button:hover{background-color:#2980b9}
    code{font-family:monospace;background-color:#f8f9fa;padding:2px 4px;border-radius:3px}</style>';
    echo '</head><body>';
    echo '<h1>SlimmerMetAI.com Database Installatie</h1>';
    echo '<div class="warning"><strong>Waarschuwing:</strong> Dit script zal de database installeren of updaten. Maak een backup van bestaande data.</div>';
    
    // Toon database info zonder gevoelige gegevens
    echo '<h3>Database informatie:</h3>';
    echo '<ul>';
    echo '<li>Database host: ' . htmlspecialchars($config['db_host']) . '</li>';
    echo '<li>Database naam: ' . htmlspecialchars($config['db_name']) . '</li>';
    echo '<li>Database gebruiker: ' . htmlspecialchars($config['db_user']) . '</li>';
    echo '</ul>';
    
    // Toon aantal queries die uitgevoerd zullen worden
    $queries = preg_split('/;\s*$/m', $sqlScript);
    $validQueries = array_filter($queries);
    echo '<p>Aantal uit te voeren database queries: ' . count($validQueries) . '</p>';
    
    echo '<p>De volgende tabellen worden aangemaakt of bijgewerkt:</p>';
    echo '<ul>';
    preg_match_all('/CREATE TABLE IF NOT EXISTS\s+([a-zA-Z0-9_]+)/i', $sqlScript, $matches);
    foreach ($matches[1] as $table) {
        echo '<li>' . htmlspecialchars($table) . '</li>';
    }
    echo '</ul>';
    
    echo '<p><a href="?key=' . urlencode($installKey) . '&confirm=true" class="button">Installatie starten</a></p>';
    echo '</body></html>';
    exit;
}

// Installatie uitvoeren
try {
    $pdo = new PDO("mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4", 
                   $config['db_user'], 
                   $config['db_pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Splits het SQL-script op in afzonderlijke queries
    $queries = preg_split('/;\s*$/m', $sqlScript);
    
    // Voer elke query uit
    $successCount = 0;
    $totalCount = 0;
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (empty($query)) continue;
        
        $totalCount++;
        
        try {
            $stmt = $pdo->prepare($query);
            $result = $stmt->execute();
            
            if ($result) {
                $successCount++;
            }
        } catch (PDOException $e) {
            // Negeer fouten als de tabel al bestaat of vergelijkbare fouten
            if (strpos($e->getMessage(), '1062 Duplicate') !== false) {
                // Dit is een 'duplicate entry' fout, wat normaal kan zijn
                $successCount++;
            } else {
                // Toon de fout maar ga door met de rest van de queries
                echo '<div style="color: red; margin: 10px 0; padding: 10px; border: 1px solid #ffcccc; background-color: #ffeeee;">';
                echo '<strong>Fout bij query:</strong><br>';
                echo '<pre>' . htmlspecialchars($query) . '</pre>';
                echo '<strong>Foutmelding:</strong> ' . htmlspecialchars($e->getMessage());
                echo '</div>';
            }
        }
    }
    
    // Toon resultaat
    echo '<html><head><title>SlimmerMetAI.com Database Installatie</title>';
    echo '<style>body{font-family:Arial,sans-serif;max-width:800px;margin:0 auto;padding:20px}
    h1{color:#2c3e50}
    .success{background-color:#dff0d8;border-left:5px solid #5cb85c;padding:10px;margin:15px 0}
    .warning{background-color:#fcf8e3;border-left:5px solid #f0ad4e;padding:10px;margin:15px 0}
    </style>';
    echo '</head><body>';
    echo '<h1>SlimmerMetAI.com Database Installatie</h1>';
    
    if ($successCount === $totalCount) {
        echo '<div class="success">
            <strong>Installatie succesvol!</strong><br>
            Alle ' . $successCount . ' queries zijn uitgevoerd.
        </div>';
    } else {
        echo '<div class="warning">
            <strong>Installatie gedeeltelijk succesvol.</strong><br>
            ' . $successCount . ' van de ' . $totalCount . ' queries zijn uitgevoerd.
        </div>';
    }
    
    echo '<p style="color:red;font-weight:bold">
        BELANGRIJKE VEILIGHEIDSWAARSCHUWING: Verwijder dit bestand (install.php) direct na gebruik!
    </p>';
    
    // Toon admin inloggegevens
    echo '<div style="margin-top: 20px; padding: 15px; border: 1px solid #ddd; background-color: #f9f9f9;">';
    echo '<h3>Admin inloggegevens:</h3>';
    echo '<p>E-mail: admin@slimmermetai.com<br>';
    echo 'Wachtwoord: Admin123!</p>';
    echo '<p style="color: #e74c3c; font-weight: bold;">Wijzig dit wachtwoord direct na je eerste inlog!</p>';
    echo '</div>';
    
    echo '</body></html>';
    
} catch (PDOException $e) {
    // Toon algemene foutmelding bij verbindingsproblemen
    echo '<html><head><title>SlimmerMetAI.com Database Installatie - Fout</title>';
    echo '<style>body{font-family:Arial,sans-serif;max-width:800px;margin:0 auto;padding:20px}
    h1{color:#2c3e50}
    .error{background-color:#f2dede;border-left:5px solid #d9534f;padding:10px;margin:15px 0}
    </style>';
    echo '</head><body>';
    echo '<h1>SlimmerMetAI.com Database Installatie - Fout</h1>';
    echo '<div class="error">
        <strong>Er is een fout opgetreden:</strong><br>
        ' . htmlspecialchars($e->getMessage()) . '
    </div>';
    echo '<p>Controleer de database instellingen in het config.php bestand en probeer het opnieuw.</p>';
    echo '</body></html>';
}
?> 