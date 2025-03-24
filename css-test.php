<?php
// Eenvoudige CSS-testpagina
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSS Test - Slimmer met AI</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .test-blocks {
            padding: 20px;
            margin: 20px;
        }
        .test-block {
            padding: 20px;
            margin: 20px 0;
            background-color: #f5f5f5;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>CSS Test Pagina</h1>
        <p>Deze pagina test of de CSS correct wordt geladen.</p>
        
        <div class="test-blocks">
            <div class="test-block">
                <h2>Test van container class</h2>
                <p>Deze div heeft de 'container' class uit style.css.</p>
            </div>
            
            <div class="test-block">
                <h3>Test van knoppen</h3>
                <a href="#" class="btn btn-primary">Primaire knop</a>
                <a href="#" class="btn btn-secondary">Secundaire knop</a>
            </div>
            
            <div class="test-block">
                <h3>Test van kleuren</h3>
                <p>De primaire kleur zou blauw-paars moeten zijn (--primary-color: #5852f2)</p>
                <p>De accentkleur zou roze moeten zijn (--accent-color: #db2777)</p>
            </div>
        </div>
        
        <p>Als de styling correct wordt geladen, zou je containers, knoppen, en specifieke kleuren moeten zien.</p>
        <p>CSS-bestand pad: <?php echo realpath('css/style.css') ? realpath('css/style.css') : 'CSS-bestand niet gevonden'; ?></p>
    </div>
</body>
</html> 