<?php
// Test pagina met verschillende CSS paden
$domein = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSS Pad Test - Slimmer met AI</title>
    
    <!-- Test 1: Relatief pad vanuit huidige map -->
    <link rel="stylesheet" href="css/style.css" id="css-test-1">
    
    <!-- Test 2: Pad vanaf de root -->
    <link rel="stylesheet" href="/css/style.css" id="css-test-2">
    
    <!-- Test 3: Absoluut pad met domein -->
    <link rel="stylesheet" href="<?php echo $domein; ?>/css/style.css" id="css-test-3">
    
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .test-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; }
        .btn { display: inline-block; padding: 10px 15px; text-decoration: none; margin: 5px; }
    </style>
</head>
<body>
    <h1>CSS Pad Test</h1>
    <p>Deze pagina test verschillende manieren om het CSS-bestand te laden. Controleer welke knop hieronder getoond wordt met de juiste styling.</p>
    
    <div class="test-section">
        <h2>Test 1: Relatief pad (css/style.css)</h2>
        <p>Knop zou de primaire stijl moeten hebben als het CSS-bestand correct wordt geladen:</p>
        <a href="#" class="btn btn-primary" id="test-btn-1">Test Knop 1</a>
        <button onclick="disableCSS(1)">Schakel deze CSS uit</button>
        <button onclick="enableCSS(1)">Schakel deze CSS in</button>
    </div>
    
    <div class="test-section">
        <h2>Test 2: Pad vanaf de root (/css/style.css)</h2>
        <p>Knop zou de primaire stijl moeten hebben als het CSS-bestand correct wordt geladen:</p>
        <a href="#" class="btn btn-primary" id="test-btn-2">Test Knop 2</a>
        <button onclick="disableCSS(2)">Schakel deze CSS uit</button>
        <button onclick="enableCSS(2)">Schakel deze CSS in</button>
    </div>
    
    <div class="test-section">
        <h2>Test 3: Absoluut pad (<?php echo $domein; ?>/css/style.css)</h2>
        <p>Knop zou de primaire stijl moeten hebben als het CSS-bestand correct wordt geladen:</p>
        <a href="#" class="btn btn-primary" id="test-btn-3">Test Knop 3</a>
        <button onclick="disableCSS(3)">Schakel deze CSS uit</button>
        <button onclick="enableCSS(3)">Schakel deze CSS in</button>
    </div>
    
    <div class="test-section">
        <h2>CSS Bestand Direct Openen</h2>
        <p>Probeer het CSS-bestand direct te openen via deze links:</p>
        <ul>
            <li><a href="css/style.css" target="_blank">css/style.css (relatief)</a></li>
            <li><a href="/css/style.css" target="_blank">/css/style.css (vanaf root)</a></li>
            <li><a href="<?php echo $domein; ?>/css/style.css" target="_blank"><?php echo $domein; ?>/css/style.css (absoluut)</a></li>
        </ul>
    </div>
    
    <script>
        function disableCSS(testNum) {
            document.getElementById('css-test-' + testNum).disabled = true;
        }
        
        function enableCSS(testNum) {
            document.getElementById('css-test-' + testNum).disabled = false;
        }
    </script>
</body>
</html> 