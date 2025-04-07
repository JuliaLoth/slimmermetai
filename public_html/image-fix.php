<?php
// Diagnosepagina voor afbeeldingsproblemen
header('Content-Type: text/html; charset=utf-8');

// Functie om een map te maken als deze nog niet bestaat
function createDirectoryIfNotExists($path) {
    if (!file_exists($path)) {
        return mkdir($path, 0755, true);
    }
    return true;
}

// Functie om een bestand te kopiëren
function copyFileWithFallback($source, $destination) {
    if (file_exists($source)) {
        return copy($source, $destination);
    }
    return false;
}

// Debugging info tonen
$docRoot = $_SERVER['DOCUMENT_ROOT'];
$currentDir = dirname(__FILE__);
$imagesDir = $currentDir . '/images';
$coursesDir = $imagesDir . '/courses';

// De courses map aanmaken als deze nog niet bestaat
$coursesCreated = createDirectoryIfNotExists($coursesDir);

// Bestandoperaties uitvoeren
$results = [];

// 1. Hero background SVG hernoemen of kopiëren
if (file_exists($imagesDir . '/hero background def.svg') && !file_exists($imagesDir . '/hero-background.svg')) {
    $results['hero-rename'] = copy($imagesDir . '/hero background def.svg', $imagesDir . '/hero-background.svg');
} else {
    $results['hero-rename'] = 'Bestand bestaat al of bronbestand niet gevonden';
}

// 2. Basis afbeeldingen kopiëren
$sourceFiles = [
    'ai-basics.svg' => 'basics.jpg',
    'prompt-engineering.svg' => 'prompting.jpg',
    'workflow-automation.svg' => 'workflow.jpg'
];

foreach ($sourceFiles as $source => $destination) {
    if (file_exists($imagesDir . '/' . $source)) {
        $results[$destination] = copy($imagesDir . '/' . $source, $coursesDir . '/' . $destination);
    } else {
        $results[$destination] = 'Bronbestand ' . $source . ' niet gevonden';
    }
}

// HTML output
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Afbeeldingsfix | Slimmer met AI</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1 { color: #333; }
        .result { margin: 10px 0; padding: 10px; border: 1px solid #ddd; }
        .success { background-color: #d4edda; }
        .failure { background-color: #f8d7da; }
        pre { background: #f4f4f4; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Afbeeldingsfix Diagnose</h1>
    
    <h2>Systeeminformatie</h2>
    <pre>
Document Root: <?php echo $docRoot; ?>
Huidige map: <?php echo $currentDir; ?>
Images map: <?php echo $imagesDir; ?>
Courses map: <?php echo $coursesDir; ?>
Courses map aangemaakt: <?php echo $coursesCreated ? 'Ja' : 'Nee (bestaat mogelijk al)'; ?>
    </pre>
    
    <h2>Resultaten</h2>
    <?php foreach ($results as $operation => $result): ?>
        <div class="result <?php echo $result === true ? 'success' : 'failure'; ?>">
            <strong><?php echo htmlspecialchars($operation); ?>:</strong> 
            <?php echo is_bool($result) ? ($result ? 'Succes' : 'Mislukt') : htmlspecialchars($result); ?>
        </div>
    <?php endforeach; ?>
    
    <h2>Bestaande afbeeldingen</h2>
    <h3>Images map:</h3>
    <ul>
    <?php 
    if (is_dir($imagesDir)) {
        $files = scandir($imagesDir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..' && !is_dir($imagesDir . '/' . $file)) {
                echo '<li>' . htmlspecialchars($file) . ' (' . filesize($imagesDir . '/' . $file) . ' bytes)</li>';
            }
        }
    } else {
        echo '<li>Kan map niet openen</li>';
    }
    ?>
    </ul>
    
    <h3>Courses map:</h3>
    <ul>
    <?php 
    if (is_dir($coursesDir)) {
        $files = scandir($coursesDir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..' && !is_dir($coursesDir . '/' . $file)) {
                echo '<li>' . htmlspecialchars($file) . ' (' . filesize($coursesDir . '/' . $file) . ' bytes)</li>';
            }
        }
    } else {
        echo '<li>Kan map niet openen</li>';
    }
    ?>
    </ul>

    <h2>Test afbeeldingsverwijzingen</h2>
    <p>Hero background:</p>
    <img src="/images/hero-background.svg" alt="Hero background" style="max-width: 200px; border: 1px solid #ddd;">
    
    <p>Cursusafbeeldingen:</p>
    <img src="/images/ai-basics.svg" alt="AI Basis" style="max-width: 200px; border: 1px solid #ddd;">
    <img src="/images/prompt-engineering.svg" alt="Prompting" style="max-width: 200px; border: 1px solid #ddd;">
    <img src="/images/workflow-automation.svg" alt="Workflow" style="max-width: 200px; border: 1px solid #ddd;">
</body>
</html> 