<?php
// Script om bestandsrechten te controleren en te corrigeren

// Voorkom directe uitvoering op productie zonder authenticatie
if (isset($_GET['run'])) {
    
    echo '<h1>Bestandsrechten Check & Fix</h1>';
    
    // Controleer of de css map bestaat
    $css_dir = 'css';
    if (is_dir($css_dir)) {
        echo "<p>✅ CSS map bestaat</p>";
        
        // Controleer of we de map kunnen lezen
        if (is_readable($css_dir)) {
            echo "<p>✅ CSS map is leesbaar</p>";
        } else {
            echo "<p>❌ CSS map is NIET leesbaar</p>";
            
            // Probeer rechten aan te passen
            if (@chmod($css_dir, 0755)) {
                echo "<p>✅ CSS map rechten aangepast naar 755</p>";
            } else {
                echo "<p>❌ Kon CSS map rechten NIET aanpassen</p>";
            }
        }
        
        // Controleer alle bestanden in de CSS map
        $css_files = scandir($css_dir);
        echo "<h2>CSS Bestanden:</h2><ul>";
        foreach ($css_files as $file) {
            if ($file != '.' && $file != '..') {
                $file_path = "$css_dir/$file";
                echo "<li>$file: ";
                
                // Bestand bestaat
                if (file_exists($file_path)) {
                    echo "✅ Bestaat, ";
                    
                    // Bestand is leesbaar
                    if (is_readable($file_path)) {
                        echo "✅ Leesbaar, ";
                    } else {
                        echo "❌ NIET leesbaar, ";
                        // Probeer rechten aan te passen
                        if (@chmod($file_path, 0644)) {
                            echo "✅ Rechten aangepast naar 644, ";
                        } else {
                            echo "❌ Kon rechten NIET aanpassen, ";
                        }
                    }
                    
                    // Bestandsgrootte
                    $size = filesize($file_path);
                    echo "Grootte: $size bytes";
                } else {
                    echo "❌ Bestand bestaat NIET";
                }
                
                echo "</li>";
            }
        }
        echo "</ul>";
    } else {
        echo "<p>❌ CSS map bestaat NIET!</p>";
        
        // Probeer de map aan te maken
        if (@mkdir($css_dir, 0755)) {
            echo "<p>✅ CSS map aangemaakt</p>";
        } else {
            echo "<p>❌ Kon CSS map NIET aanmaken</p>";
        }
    }
    
    // Controleer de style.css in de CSS map
    $css_file = "css/style.css";
    echo "<h2>Controle $css_file:</h2>";
    
    if (file_exists($css_file)) {
        echo "<p>✅ $css_file bestaat</p>";
        
        // Bestand is leesbaar
        if (is_readable($css_file)) {
            echo "<p>✅ $css_file is leesbaar</p>";
        } else {
            echo "<p>❌ $css_file is NIET leesbaar</p>";
            
            // Probeer rechten aan te passen
            if (@chmod($css_file, 0644)) {
                echo "<p>✅ $css_file rechten aangepast naar 644</p>";
            } else {
                echo "<p>❌ Kon $css_file rechten NIET aanpassen</p>";
            }
        }
        
        // Bestandsgrootte
        $size = filesize($css_file);
        echo "<p>Bestandsgrootte: $size bytes</p>";
        
        // Eerste 100 karakters tonen om te verifiëren dat het de juiste inhoud heeft
        $content = file_get_contents($css_file, false, null, 0, 100);
        echo "<p>Begin van bestand: <pre>" . htmlspecialchars($content) . "...</pre></p>";
        
    } else {
        echo "<p>❌ $css_file bestaat NIET!</p>";
    }
    
    // Server informatie
    echo "<h2>Server Informatie:</h2>";
    echo "<p>PHP versie: " . phpversion() . "</p>";
    echo "<p>Server software: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
    echo "<p>Document root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
    echo "<p>Huidige script: " . $_SERVER['SCRIPT_FILENAME'] . "</p>";
    echo "<p>HTTP Host: " . $_SERVER['HTTP_HOST'] . "</p>";
    
    // Toon een voorbeeld van een inline gestileerde knop
    echo "<h2>Inline Styling Test:</h2>";
    echo '<a href="#" style="display: inline-block; padding: 10px 15px; background-color: #5852f2; color: white; text-decoration: none; border-radius: 4px; font-weight: bold;">Inline Gestileerde Knop</a>';
    
} else {
    echo '<h1>Bestandsrechten Fix Tool</h1>';
    echo '<p>Klik op de knop om bestandsrechten te controleren en te proberen problemen op te lossen.</p>';
    echo '<p><a href="?run=1" style="display: inline-block; padding: 10px 15px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px;">Start Controle & Fix</a></p>';
    echo '<p><strong>Let op:</strong> Gebruik deze tool alleen als je problemen hebt met het laden van CSS bestanden.</p>';
}
?> 