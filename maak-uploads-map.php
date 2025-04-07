<?php
/**
 * Script om de juiste mappenstructuur voor uploads te maken
 * Voer dit script uit na het uploaden van je website via FTP
 */

// Defineer basis uploads map
$uploadsDir = __DIR__ . '/uploads';

// Definieer submappen die moeten worden aangemaakt
$subDirs = [
    '/profile_pictures',
    '/temp',
];

// Functie om mappen aan te maken en rechten in te stellen
function createDirectory($dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "Map aangemaakt: $dir (rechten: 0755)<br>";
            
            // Maak een .htaccess bestand om directe toegang te beperken
            $htaccess = $dir . '/.htaccess';
            if (!file_exists($htaccess)) {
                $content = "# Voorkom directory listing\nOptions -Indexes\n\n";
                
                // Voor profile_pictures map, sta alleen afbeeldingen toe
                if (strpos($dir, 'profile_pictures') !== false) {
                    $content .= "# Sta alleen specifieke bestandstypen toe\n";
                    $content .= "<FilesMatch \"\\.(jpg|jpeg|png|gif)$\">\n";
                    $content .= "    Allow from all\n";
                    $content .= "</FilesMatch>\n";
                    $content .= "<FilesMatch \"^(?!\\.(jpg|jpeg|png|gif)$).*$\">\n";
                    $content .= "    Order deny,allow\n";
                    $content .= "    Deny from all\n";
                    $content .= "</FilesMatch>\n";
                }
                
                file_put_contents($htaccess, $content);
                echo "- .htaccess bestand aangemaakt voor beveiliging<br>";
            }
            
            // Maak een index.php bestand om directe toegang verder te beperken
            $index = $dir . '/index.php';
            if (!file_exists($index)) {
                $content = "<?php\n// Voorkom directory listing\nheader('Location: /index.php');\nexit;\n";
                file_put_contents($index, $content);
                echo "- index.php bestand aangemaakt voor beveiliging<br>";
            }
            
            return true;
        } else {
            echo "FOUT: Kon map niet aanmaken: $dir<br>";
            echo "Controleer of de webserver schrijfrechten heeft.<br>";
            return false;
        }
    } else {
        echo "Map bestaat al: $dir<br>";
        
        // Controleer en pas rechten aan
        if (!is_writable($dir)) {
            if (chmod($dir, 0755)) {
                echo "- Rechten aangepast naar 0755<br>";
            } else {
                echo "- WAARSCHUWING: Kon rechten niet aanpassen. De map moet schrijfbaar zijn voor de webserver.<br>";
            }
        } else {
            echo "- Rechten zijn al correct ingesteld<br>";
        }
        
        return true;
    }
}

echo "<h1>Maken van uploads mappenstructuur</h1>";

// Maak de hoofdmap aan
if (createDirectory($uploadsDir)) {
    echo "<hr>";
    
    // Maak submappen aan
    foreach ($subDirs as $subDir) {
        createDirectory($uploadsDir . $subDir);
    }
    
    echo "<hr>";
    echo "<strong>Alle benodigde mappen zijn aangemaakt of gecontroleerd.</strong><br><br>";
    echo "Als je de melding \"Kon map niet aanmaken\" of \"Kon rechten niet aanpassen\" ziet, ";
    echo "moet je de mappen handmatig aanmaken via FTP en de rechten instellen op 755 (rwxr-xr-x).<br><br>";
    echo "Verwijder dit script na gebruik voor de veiligheid!";
} else {
    echo "<hr>";
    echo "<strong>Kon de basismap voor uploads niet aanmaken. </strong><br><br>";
    echo "Controleer of het pad correct is en of de webserver schrijfrechten heeft op de juiste locatie.<br><br>";
    echo "Je kunt ook handmatig de volgende mappenstructuur aanmaken via FTP:<br>";
    echo "<code>/uploads/<br>/uploads/profile_pictures/<br>/uploads/temp/</code><br><br>";
    echo "Zorg ervoor dat alle mappen schrijfrechten hebben (chmod 755 of 775).";
}
?> 