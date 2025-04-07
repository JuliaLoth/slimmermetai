<?php
// Simpel script om JPG-bestanden te genereren op basis van SVG-bestanden
header('Content-Type: text/html; charset=utf-8');

// Configuratie
$imageDir = __DIR__ . '/images';
$coursesDir = $imageDir . '/courses';
$width = 800;
$height = 600;

// Zorgen dat de courses directory bestaat
if (!file_exists($coursesDir)) {
    mkdir($coursesDir, 0755, true);
}

// Kleurconfiguratie voor de afbeeldingen
$colors = [
    'basics' => ['bg' => '#1a73e8', 'text' => '#ffffff'],
    'prompting' => ['bg' => '#34a853', 'text' => '#ffffff'],
    'workflow' => ['bg' => '#ea4335', 'text' => '#ffffff']
];

// Functie om een basis afbeelding te genereren met tekst
function createImageWithText($width, $height, $bgColor, $textColor, $text, $outputPath) {
    // Maak een lege afbeelding
    $image = imagecreatetruecolor($width, $height);
    
    // Kleurinstelling
    $backgroundColor = imagecolorallocate(
        $image, 
        hexdec(substr($bgColor, 1, 2)), 
        hexdec(substr($bgColor, 3, 2)), 
        hexdec(substr($bgColor, 5, 2))
    );
    
    $textColorValue = imagecolorallocate(
        $image, 
        hexdec(substr($textColor, 1, 2)), 
        hexdec(substr($textColor, 3, 2)), 
        hexdec(substr($textColor, 5, 2))
    );
    
    // Vul de achtergrond
    imagefill($image, 0, 0, $backgroundColor);
    
    // Voeg een verloop toe
    $gradientColor = imagecolorallocatealpha($image, 0, 0, 0, 75);
    imagefilledrectangle($image, 0, 0, $width, $height, $gradientColor);
    
    // Voeg tekst toe
    $fontSize = 5; // Groot formaat voor GD font (1-5)
    $fontWidth = imagefontwidth($fontSize);
    $fontHeight = imagefontheight($fontSize);
    
    // Centreer de tekst
    $textWidth = $fontWidth * strlen($text);
    $textX = ($width - $textWidth) / 2;
    $textY = ($height - $fontHeight) / 2;
    
    imagestring($image, $fontSize, $textX, $textY, $text, $textColorValue);
    
    // Voeg een rand toe
    $borderColor = imagecolorallocate($image, 255, 255, 255);
    imagerectangle($image, 0, 0, $width-1, $height-1, $borderColor);
    
    // Sla de afbeelding op als JPG
    imagejpeg($image, $outputPath, 90);
    
    // Maak het geheugen vrij
    imagedestroy($image);
    
    return file_exists($outputPath);
}

// Genereer de afbeeldingen
$imageResults = [];

// Basis cursusafbeelding
$imageResults['basics.jpg'] = createImageWithText(
    $width, 
    $height, 
    $colors['basics']['bg'], 
    $colors['basics']['text'], 
    'AI Basis Cursus', 
    $coursesDir . '/basics.jpg'
);

// Prompting cursusafbeelding
$imageResults['prompting.jpg'] = createImageWithText(
    $width, 
    $height, 
    $colors['prompting']['bg'], 
    $colors['prompting']['text'], 
    'Effectief Prompten', 
    $coursesDir . '/prompting.jpg'
);

// Workflow cursusafbeelding 
$imageResults['workflow.jpg'] = createImageWithText(
    $width, 
    $height, 
    $colors['workflow']['bg'], 
    $colors['workflow']['text'], 
    'AI Workflows', 
    $coursesDir . '/workflow.jpg'
);

// Controleer de hero background SVG
if (!file_exists($imageDir . '/hero-background.svg') && file_exists($imageDir . '/hero background def.svg')) {
    $imageResults['hero-rename'] = copy(
        $imageDir . '/hero background def.svg', 
        $imageDir . '/hero-background.svg'
    );
}

// HTML output
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cursusafbeeldingen Generator | Slimmer met AI</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2 { color: #333; }
        .result { margin: 10px 0; padding: 10px; border: 1px solid #ddd; }
        .success { background-color: #d4edda; }
        .failure { background-color: #f8d7da; }
        .images { display: flex; flex-wrap: wrap; gap: 20px; margin-top: 20px; }
        .image-preview { border: 1px solid #ddd; padding: 10px; text-align: center; }
        .image-preview img { max-width: 300px; max-height: 200px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <h1>Cursusafbeeldingen Generator</h1>
    
    <h2>Resultaten</h2>
    <?php foreach ($imageResults as $imageName => $success): ?>
        <div class="result <?php echo $success ? 'success' : 'failure'; ?>">
            <strong><?php echo htmlspecialchars($imageName); ?>:</strong> 
            <?php echo $success ? 'Succesvol gegenereerd' : 'Genereren mislukt'; ?>
        </div>
    <?php endforeach; ?>
    
    <h2>Gegenereerde afbeeldingen preview</h2>
    <div class="images">
        <?php if (file_exists($imageDir . '/ai-basics.svg')): ?>
            <div class="image-preview">
                <img src="/images/ai-basics.svg" alt="AI Basis Cursus">
                <p>AI Basis Cursus</p>
            </div>
        <?php endif; ?>
        
        <?php if (file_exists($imageDir . '/prompt-engineering.svg')): ?>
            <div class="image-preview">
                <img src="/images/prompt-engineering.svg" alt="Effectief Prompten">
                <p>Effectief Prompten</p>
            </div>
        <?php endif; ?>
        
        <?php if (file_exists($imageDir . '/workflow-automation.svg')): ?>
            <div class="image-preview">
                <img src="/images/workflow-automation.svg" alt="AI Workflows">
                <p>AI Workflows</p>
            </div>
        <?php endif; ?>
    </div>
    
    <h2>Navigatie</h2>
    <p><a href="/">Terug naar homepagina</a></p>
</body>
</html> 