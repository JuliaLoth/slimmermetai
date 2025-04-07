<?php
/**
 * SlimmerMetAI.com API Diagnose Pagina
 * 
 * Deze pagina controleert of de API-installatie correct is ingesteld.
 * Verwijder deze pagina in productie of beveilig hem met een wachtwoord.
 */

// Defineer SITE_ROOT als dat nog niet is gebeurd
if (!defined('SITE_ROOT')) {
    define('SITE_ROOT', dirname(__DIR__));
}

// Laad configuratie
require_once __DIR__ . '/config.php';

// Controleer of er een check key is meegegeven
$checkKey = isset($_GET['key']) ? $_GET['key'] : '';
$expectedKey = getenv('CHECK_KEY') ?: 'slimmermetai_check_2023';

if ($checkKey !== $expectedKey) {
    http_response_code(403);
    die("Ongeldige check sleutel");
}

// Start HTML output
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SlimmerMetAI.com API Diagnose</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            color: #333;
        }
        h1 {
            color: #2c3e50;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 10px;
        }
        h2 {
            color: #3498db;
            margin-top: 30px;
        }
        .success {
            background-color: #d4edda;
            border-left: 5px solid #28a745;
            padding: 10px 15px;
            margin: 10px 0;
        }
        .warning {
            background-color: #fff3cd;
            border-left: 5px solid #ffc107;
            padding: 10px 15px;
            margin: 10px 0;
        }
        .error {
            background-color: #f8d7da;
            border-left: 5px solid #dc3545;
            padding: 10px 15px;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        code {
            background-color: #f8f9fa;
            padding: 2px 4px;
            border-radius: 3px;
            font-family: Consolas, Monaco, 'Andale Mono', monospace;
        }
        .note {
            font-style: italic;
            color: #666;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <h1>SlimmerMetAI.com API Diagnose</h1>
    
    <div class="warning">
        <strong>Veiligheidsadvies:</strong> Verwijder dit bestand voor gebruik in productie of beveilig het met een sterke sleutel.
    </div>
    
    <h2>Systeeminformatie</h2>
    <table>
        <tr>
            <th>Item</th>
            <th>Waarde</th>
            <th>Status</th>
        </tr>
        <tr>
            <td>PHP Versie</td>
            <td><?php echo phpversion(); ?></td>
            <td><?php echo version_compare(phpversion(), '7.4.0', '>=') ? '<span class="success">OK</span>' : '<span class="error">Te oud (7.4+ aanbevolen)</span>'; ?></td>
        </tr>
        <tr>
            <td>PDO MySQL Extensie</td>
            <td><?php echo extension_loaded('pdo_mysql') ? 'Beschikbaar' : 'Niet beschikbaar'; ?></td>
            <td><?php echo extension_loaded('pdo_mysql') ? '<span class="success">OK</span>' : '<span class="error">Vereist voor databaseconnectie</span>'; ?></td>
        </tr>
        <tr>
            <td>JSON Extensie</td>
            <td><?php echo extension_loaded('json') ? 'Beschikbaar' : 'Niet beschikbaar'; ?></td>
            <td><?php echo extension_loaded('json') ? '<span class="success">OK</span>' : '<span class="error">Vereist voor API werking</span>'; ?></td>
        </tr>
        <tr>
            <td>GD Extensie</td>
            <td><?php echo extension_loaded('gd') ? 'Beschikbaar' : 'Niet beschikbaar'; ?></td>
            <td><?php echo extension_loaded('gd') ? '<span class="success">OK</span>' : '<span class="warning">Aanbevolen voor afbeeldingsverwerking</span>'; ?></td>
        </tr>
        <tr>
            <td>OpenSSL Extensie</td>
            <td><?php echo extension_loaded('openssl') ? 'Beschikbaar' : 'Niet beschikbaar'; ?></td>
            <td><?php echo extension_loaded('openssl') ? '<span class="success">OK</span>' : '<span class="warning">Aanbevolen voor veilige verbindingen</span>'; ?></td>
        </tr>
        <tr>
            <td>Max Upload Size</td>
            <td><?php echo ini_get('upload_max_filesize'); ?></td>
            <td><?php echo (intval(ini_get('upload_max_filesize')) >= 2) ? '<span class="success">OK</span>' : '<span class="warning">Aanbevolen: minstens 2MB</span>'; ?></td>
        </tr>
        <tr>
            <td>Debug Mode</td>
            <td><?php echo isset($config['debug_mode']) && $config['debug_mode'] ? 'Ingeschakeld' : 'Uitgeschakeld'; ?></td>
            <td><?php echo isset($config['debug_mode']) && $config['debug_mode'] ? '<span class="warning">Uitschakelen in productie</span>' : '<span class="success">OK</span>'; ?></td>
        </tr>
    </table>
    
    <h2>Databaseverbinding</h2>
    <?php
    $db_status = '';
    $db_message = '';
    
    try {
        $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];
        
        $pdo_check = new PDO($dsn, $config['db_user'], $config['db_pass'], $options);
        
        // Controleer of de vereiste tabellen bestaan
        $required_tables = ['users', 'refresh_tokens', 'login_attempts', 'email_tokens'];
        $missing_tables = [];
        
        $stmt = $pdo_check->query("SHOW TABLES");
        $existing_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($required_tables as $table) {
            if (!in_array($table, $existing_tables)) {
                $missing_tables[] = $table;
            }
        }
        
        if (empty($missing_tables)) {
            $db_status = 'success';
            $db_message = 'Databaseverbinding succesvol en alle vereiste tabellen zijn aanwezig.';
        } else {
            $db_status = 'warning';
            $db_message = 'Databaseverbinding succesvol, maar de volgende tabellen ontbreken: ' . implode(', ', $missing_tables);
        }
    } catch (PDOException $e) {
        $db_status = 'error';
        $db_message = 'Databaseverbinding mislukt: ' . $e->getMessage();
    }
    ?>
    
    <div class="<?php echo $db_status; ?>">
        <?php echo $db_message; ?>
    </div>
    
    <h2>Bestandsrechten</h2>
    <?php
    $uploads_dir = dirname(SITE_ROOT) . '/uploads';
    $profile_pictures_dir = $uploads_dir . '/profile_pictures';
    $documents_dir = $uploads_dir . '/documents';
    
    $upload_test_file = $uploads_dir . '/test_write_permission.txt';
    ?>
    
    <table>
        <tr>
            <th>Map</th>
            <th>Bestaat</th>
            <th>Schrijfbaar</th>
        </tr>
        <tr>
            <td><?php echo $uploads_dir; ?></td>
            <td><?php echo is_dir($uploads_dir) ? 'Ja' : 'Nee'; ?></td>
            <td>
                <?php 
                if (!is_dir($uploads_dir)) {
                    echo '<span class="error">Map bestaat niet</span>';
                } else {
                    $is_writable = is_writable($uploads_dir);
                    echo $is_writable ? '<span class="success">Ja</span>' : '<span class="error">Nee</span>';
                    
                    if ($is_writable) {
                        // Test daadwerkelijk schrijven
                        $write_test = @file_put_contents($upload_test_file, 'Test schrijfrechten');
                        if ($write_test === false) {
                            echo ' <span class="error">(Kan niet schrijven ondanks rechten)</span>';
                        } else {
                            @unlink($upload_test_file);
                        }
                    }
                }
                ?>
            </td>
        </tr>
        <tr>
            <td><?php echo $profile_pictures_dir; ?></td>
            <td><?php echo is_dir($profile_pictures_dir) ? 'Ja' : 'Nee'; ?></td>
            <td>
                <?php 
                if (!is_dir($profile_pictures_dir)) {
                    echo '<span class="error">Map bestaat niet</span>';
                } else {
                    echo is_writable($profile_pictures_dir) ? '<span class="success">Ja</span>' : '<span class="error">Nee</span>';
                }
                ?>
            </td>
        </tr>
        <tr>
            <td><?php echo $documents_dir; ?></td>
            <td><?php echo is_dir($documents_dir) ? 'Ja' : 'Nee'; ?></td>
            <td>
                <?php 
                if (!is_dir($documents_dir)) {
                    echo '<span class="error">Map bestaat niet</span>';
                } else {
                    echo is_writable($documents_dir) ? '<span class="success">Ja</span>' : '<span class="error">Nee</span>';
                }
                ?>
            </td>
        </tr>
    </table>
    
    <h2>API Endpoints</h2>
    <table>
        <tr>
            <th>Endpoint</th>
            <th>Bestand</th>
            <th>Status</th>
        </tr>
        <?php
        $endpoints = [
            '/api/auth/login' => __DIR__ . '/auth/login.php',
            '/api/auth/register' => __DIR__ . '/auth/register.php',
            '/api/auth/logout' => __DIR__ . '/auth/logout.php',
            '/api/auth/refresh-token' => __DIR__ . '/auth/refresh-token.php',
            '/api/auth/forgot-password' => __DIR__ . '/auth/forgot-password.php',
            '/api/auth/reset-password' => __DIR__ . '/auth/reset-password.php',
            '/api/auth/verify-email' => __DIR__ . '/auth/verify-email.php',
            '/api/auth/me' => __DIR__ . '/auth/me.php',
            '/api/users/profile' => __DIR__ . '/users/profile.php',
            '/api/users/password' => __DIR__ . '/users/password.php'
        ];
        
        foreach ($endpoints as $endpoint => $file) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($endpoint) . '</td>';
            echo '<td>' . htmlspecialchars($file) . '</td>';
            
            if (file_exists($file)) {
                echo '<td><span class="success">Aanwezig</span></td>';
            } else {
                echo '<td><span class="error">Ontbreekt</span></td>';
            }
            
            echo '</tr>';
        }
        ?>
    </table>
    
    <h2>Belangrijke instellingen</h2>
    <table>
        <tr>
            <th>Instelling</th>
            <th>Status</th>
            <th>Advies</th>
        </tr>
        <tr>
            <td>JWT Secret</td>
            <td>
                <?php 
                if (!isset($config['jwt_secret'])) {
                    echo '<span class="error">Ontbreekt</span>';
                } elseif ($config['jwt_secret'] === 'default_jwt_secret_change_me') {
                    echo '<span class="error">Onveilig (standaardwaarde)</span>';
                } elseif (strlen($config['jwt_secret']) < 32) {
                    echo '<span class="warning">Mogelijk te kort</span>';
                } else {
                    echo '<span class="success">OK</span>';
                }
                ?>
            </td>
            <td>Gebruik een sterke unieke sleutel van minstens 32 tekens</td>
        </tr>
        <tr>
            <td>reCAPTCHA Configuratie</td>
            <td>
                <?php 
                if (!isset($config['recaptcha_site_key']) || !isset($config['recaptcha_secret_key'])) {
                    echo '<span class="warning">Niet geconfigureerd</span>';
                } elseif (empty($config['recaptcha_site_key']) || empty($config['recaptcha_secret_key'])) {
                    echo '<span class="warning">Lege waarden</span>';
                } else {
                    echo '<span class="success">Geconfigureerd</span>';
                }
                ?>
            </td>
            <td>Aanbevolen voor bescherming tegen geautomatiseerde aanvallen</td>
        </tr>
        <tr>
            <td>E-mailconfiguratie</td>
            <td>
                <?php 
                if (!isset($config['mail_from']) || empty($config['mail_from'])) {
                    echo '<span class="warning">Afzender niet ingesteld</span>';
                } elseif (
                    !isset($config['mail_host']) || 
                    !isset($config['mail_port']) || 
                    !isset($config['mail_username']) || 
                    !isset($config['mail_password'])
                ) {
                    echo '<span class="warning">SMTP niet geconfigureerd</span>';
                } else {
                    echo '<span class="success">Volledig geconfigureerd</span>';
                }
                ?>
            </td>
            <td>Nodig voor wachtwoord reset en e-mailverificatie</td>
        </tr>
        <tr>
            <td>HTTPS Enforcing</td>
            <td>
                <?php 
                if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) {
                    echo '<span class="success">HTTPS actief</span>';
                } else {
                    echo '<span class="warning">HTTPS niet actief</span>';
                }
                ?>
            </td>
            <td>Gebruik HTTPS voor alle verkeer in productie</td>
        </tr>
    </table>
    
    <p class="note">Deze check werd uitgevoerd op: <?php echo date('Y-m-d H:i:s'); ?></p>
</body>
</html> 