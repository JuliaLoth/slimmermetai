<?php
// Database configuratie
define('DB_HOST', 'localhost');
define('DB_USER', 'slimmermetai_user');
define('DB_PASS', 'je_database_wachtwoord'); // Vervang dit met een sterk wachtwoord
define('DB_NAME', 'slimmermetai_db');

// Site configuratie
define('SITE_URL', 'https://slimmermetai.com');
define('SITE_NAME', 'Slimmer met AI');
define('ADMIN_EMAIL', 'info@slimmermetai.com');

// Sessie configuratie
define('SESSION_EXPIRY', 3600); // 1 uur in seconden

// Veiligheid
define('HASH_COST', 12); // Voor bcrypt hashing

// Maak debug mode alleen aan in development omgeving
define('DEBUG_MODE', false);

// Logging configuratie
define('LOG_PATH', __DIR__ . '/../logs/');
?> 