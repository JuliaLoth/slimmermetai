<?php
// Debug pagina voor CSS problemen
$css_file = 'css/style.css';
$css_exists = file_exists($css_file);
$css_readable = is_readable($css_file);
$css_path = realpath($css_file);
$server_root = $_SERVER['DOCUMENT_ROOT'];
$full_css_path = $server_root . '/' . $css_file;
$full_css_exists = file_exists($full_css_path);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSS Debug - Slimmer met AI</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; padding: 20px; }
        h1 { color: #333; }
        .debug-info { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 4px; }
        .success { color: green; }
        .error { color: red; }
        .code { background: #f5f5f5; padding: 10px; border-radius: 4px; font-family: monospace; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>CSS Debug Informatie</h1>
    
    <div class="debug-info">
        <h2>Bestandsinformatie</h2>
        <p><strong>CSS bestand:</strong> <?php echo $css_file; ?></p>
        <p><strong>Bestand bestaat:</strong> <span class="<?php echo $css_exists ? 'success' : 'error'; ?>"><?php echo $css_exists ? 'Ja' : 'Nee'; ?></span></p>
        <p><strong>Bestand is leesbaar:</strong> <span class="<?php echo $css_readable ? 'success' : 'error'; ?>"><?php echo $css_readable ? 'Ja' : 'Nee'; ?></span></p>
        <p><strong>Volledig pad:</strong> <?php echo $css_path ?: 'Niet gevonden'; ?></p>
        <p><strong>Server root:</strong> <?php echo $server_root; ?></p>
        <p><strong>Volledig pad via server root:</strong> <?php echo $full_css_path; ?></p>
        <p><strong>CSS via server root bestaat:</strong> <span class="<?php echo $full_css_exists ? 'success' : 'error'; ?>"><?php echo $full_css_exists ? 'Ja' : 'Nee'; ?></span></p>
    </div>
    
    <div class="debug-info">
        <h2>Mapstructuur</h2>
        <p><strong>Huidige map:</strong> <?php echo getcwd(); ?></p>
        <p><strong>Inhoud van CSS map:</strong></p>
        <div class="code">
            <?php
            if (is_dir('css')) {
                $files = scandir('css');
                foreach ($files as $file) {
                    if ($file != '.' && $file != '..') {
                        echo $file . " (" . filesize('css/' . $file) . " bytes)<br>";
                    }
                }
            } else {
                echo "<span class='error'>CSS map niet gevonden!</span>";
            }
            ?>
        </div>
    </div>
    
    <div class="debug-info">
        <h2>Referentie Test</h2>
        <p>Hieronder wordt een inline CSS-stijl toegepast om te bevestigen dat styling werkt:</p>
        <div style="padding: 10px; background-color: #e0f7fa; border: 2px solid #00acc1; border-radius: 5px;">
            Als je deze blauwe box met rand ziet, werkt inline CSS correct.
        </div>
        <br>
        <p>Hieronder wordt een poging gedaan om het externe CSS-bestand te laden:</p>
        <link rel="stylesheet" href="<?php echo $css_file; ?>">
        <div class="container">
            <p>Als deze tekst gestileerd is volgens je website-stijl, dan is het CSS-bestand correct geladen.</p>
            <a href="#" class="btn btn-primary">Test Knop</a>
        </div>
    </div>
    
    <div class="debug-info">
        <h2>HTTP Headers Test</h2>
        <p>Probeer je CSS-bestand direct te openen via:</p>
        <p><a href="<?php echo $css_file; ?>" target="_blank"><?php echo $css_file; ?></a></p>
        <p>Als je de inhoud van het CSS-bestand ziet, is het bestand toegankelijk via je webserver.</p>
    </div>
    
    <div class="debug-info">
        <h2>Browser Console</h2>
        <p>Open de browser console (F12 of rechtermuisknop > Inspecteren > Console) om te controleren op fouten bij het laden van CSS.</p>
    </div>
</body>
</html> 