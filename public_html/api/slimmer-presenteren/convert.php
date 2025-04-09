<?php
// Forceer JSON content type voor de response
header('Content-Type: application/json');

// Definieer SITE_ROOT als het nog niet bestaat (nodig voor init.php)
if (!defined('SITE_ROOT')) {
    define('SITE_ROOT', dirname(dirname(dirname(__DIR__)))); // Ga 3 levels omhoog vanaf api/slimmer-presenteren/
}
require_once SITE_ROOT . '/includes/init.php'; // Laad de core initialisatie
require_once SITE_ROOT . '/vendor/autoload.php'; // **Laad Composer Autoloader**
// Aanname: init.php (of Config.php die het laadt) heeft .env al geladen

// --- Importeer PHPPresentation klassen ---
use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\Style\Alignment;
use PhpOffice\PhpPresentation\Style\Color;
use PhpOffice\PhpPresentation\Style\Bullet;
use PhpOffice\PhpPresentation\Shape\RichText; // Belangrijk voor tekst met opmaak

// --- Veiligheidschecks ---

// 1. Controleer of de gebruiker is ingelogd
if (!isLoggedIn()) {
    http_response_code(401); // Unauthorized
    echo json_encode(['status' => 'error', 'message' => 'Authenticatie vereist.']);
    exit;
}

// 2. Controleer of de gebruiker de juiste rol heeft
$allowedRoles = ['subscriber', 'admin']; // Pas aan indien nodig
if (!hasRole($allowedRoles)) {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => 'Onvoldoende rechten om deze actie uit te voeren. Vereist: ' . implode(' of ', $allowedRoles)]);
    exit;
}

// 3. Controleer of het een POST request is
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Methode niet toegestaan. Gebruik POST.']);
    exit;
}

// 4. Haal de input data op (verwacht JSON)
$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE || !isset($input['reactCode']) || !is_string($input['reactCode'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Ongeldige input. Verwacht JSON met een "reactCode" string.']);
    exit;
}

$reactCode = $input['reactCode'];

// --- AI Integratie (Claude via cURL) ---

// 1. Haal API sleutel veilig op uit .env variabelen
$apiKey = getenv('CLAUDE_API_KEY'); 
if (empty($apiKey)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server configuratiefout: CLAUDE_API_KEY niet gevonden in .env.']);
    ErrorHandler::getInstance()->logError('CLAUDE_API_KEY niet geconfigureerd in .env');
    exit;
}
$claudeApiUrl = 'https://api.anthropic.com/v1/messages';
$claudeModel = 'claude-3-haiku-20240307'; 

try {
    // 2. Definieer de prompt/instructie voor Claude
    $systemPrompt = <<<PROMPT
    Je bent een assistent die React code analyseert en omzet naar een gestructureerde JSON representatie voor een PowerPoint presentatie. 
    Focus op de zichtbare structuur zoals koppen (h1-h6), paragrafen (p), lijsten (ul, ol, li), en belangrijke divs die mogelijk secties of slides voorstellen. 
    Negeer implementatiedetails zoals state management, event handlers, en complexe logica, tenzij ze direct bijdragen aan de zichtbare structuur.
    De output MOET een valide JSON-array zijn van objecten, waarbij elk object een slide of een belangrijk onderdeel representeert. 
    Gebruik keys zoals "type" (bv. "title", "text", "list", "section_start"), "content" (de tekst of lijst-items als array van strings), en "level" (voor koppen, numeriek).

    Voorbeeld JSON output structuur:
    [
      { "type": "title", "content": "Titel van de Presentatie", "level": 1 },
      { "type": "text", "content": "Dit is een inleidende paragraaf." },
      { "type": "list", "content": ["Item 1", "Item 2", "Sub-item 2.1"] },
      { "type": "section_start", "content": "Optionele Sectie Titel", "level": 2 }
    ]

    Geef ALLEEN de JSON array terug, zonder enige uitleg ervoor of erna.
    PROMPT;

    $userMessage = "Analyseer de volgende React code en genereer de JSON output zoals beschreven in de systeemprompt:\n\n```jsx\n" . $reactCode . "\n```";

    // 3. Bereid de data voor de API call voor
    $requestData = [
        'model' => $claudeModel,
        'max_tokens' => 2048, // Pas aan indien nodig
        'system' => $systemPrompt,
        'messages' => [
            ['role' => 'user', 'content' => $userMessage]
        ]
    ];

    // 4. Initialiseer cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $claudeApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'x-api-key: ' . $apiKey, // Gebruik $apiKey uit getenv()
        'anthropic-version: 2023-06-01', 
        'content-type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); 
    curl_setopt($ch, CURLOPT_TIMEOUT, 60); 

    // 5. Voer de cURL request uit
    $responseBody = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // 6. Verwerk de response
    if ($curlError) {
        throw new Exception('cURL Fout: ' . $curlError);
    }

    if ($httpCode >= 400) {
         $errorDetails = json_decode($responseBody, true);
         $errorMessage = 'Claude API Fout (HTTP ' . $httpCode . ')';
         if (isset($errorDetails['error']['message'])) {
             $errorMessage .= ': ' . $errorDetails['error']['message'];
         } elseif ($responseBody) {
              $errorMessage .= ' - Response: ' . $responseBody;
         }
         throw new Exception($errorMessage);
    }

    $apiResponse = json_decode($responseBody, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Ongeldige JSON response ontvangen van Claude API: ' . json_last_error_msg());
    }

    if (!isset($apiResponse['content'][0]['text'])) {
         throw new Exception('Kon de verwachte content niet vinden in Claude API response.');
    }
    $aiJsonOutput = $apiResponse['content'][0]['text'];

    $structuredData = json_decode($aiJsonOutput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
         ErrorHandler::getInstance()->logError('Ongeldige JSON structuur ontvangen van Claude', ['raw_output' => $aiJsonOutput, 'json_error' => json_last_error_msg()]);
         throw new Exception('Ongeldige JSON structuur ontvangen van Claude: ' . json_last_error_msg());
    }

    // --- PowerPoint Generatie met PHPPresentation ---
    
    // 1. Zorg dat de upload map bestaat
    $uploadDirRelative = '/uploads/presentations';
    $uploadDirAbsolute = PUBLIC_ROOT . $uploadDirRelative;
    if (!is_dir($uploadDirAbsolute)) {
        if (!mkdir($uploadDirAbsolute, 0755, true)) {
             throw new Exception('Kon upload map niet aanmaken: ' . $uploadDirAbsolute);
        }
    }

    // 2. Maak een presentatie object
    $presentation = new PhpPresentation();
    
    // 3. Verwerk de gestructureerde data
    $currentSlide = null; 
    $shapeYPosition = 50; 
    $slideWidth = $presentation->getLayout()->getCX(PhpPresentation::LAYOUT_SCREEN_16X9); 
    $slideHeight = $presentation->getLayout()->getCY(PhpPresentation::LAYOUT_SCREEN_16X9); 
    $defaultMargin = 30;

    // Helper functie
    function createTextShape(RichText\Run $run, $height, $width, $offsetX, $offsetY): RichText {
        $shape = new RichText();
        $shape->setHeight($height); $shape->setWidth($width);
        $shape->setOffsetX($offsetX); $shape->setOffsetY($offsetY);
        $shape->getActiveParagraph()->addRun($run);
        return $shape;
    }

    if (empty($structuredData)) {
        $currentSlide = $presentation->getActiveSlide();
        $run = new RichText\Run(); $run->setText('Kon geen structuur uit de code halen.');
        $run->getFont()->setSize(18)->setColor(new Color(Color::COLOR_RED));
        $currentSlide->addShape(createTextShape($run, 50, $slideWidth - (2 * $defaultMargin), $defaultMargin, $shapeYPosition));
    } else {
        foreach ($structuredData as $item) {
            if ($currentSlide === null) {
                $currentSlide = $presentation->getActiveSlide();
            }

            $itemType = $item['type'] ?? 'text';
            $itemContent = $item['content'] ?? '';
            $itemLevel = $item['level'] ?? null;
            $shapeHeight = 50; $shapeWidth = $slideWidth - (2 * $defaultMargin);
            $run = new RichText\Run(); $run->getFont()->setName('Arial');

            switch ($itemType) {
                case 'title':
                    if ($itemLevel === 1 && $presentation->getSlideCount() > 0 && $currentSlide !== $presentation->getSlide(0)) { 
                         $currentSlide = $presentation->createSlide(); $shapeYPosition = $defaultMargin; 
                    } elseif ($currentSlide === null) { $currentSlide = $presentation->getActiveSlide(); }
                    $run->setText($itemContent);
                    $fontSize = match($itemLevel) { 1 => 44, 2 => 32, 3 => 28, default => 24 };
                    $run->getFont()->setSize($fontSize)->setBold(true);
                    $shapeHeight = $fontSize * 1.5; 
                    $currentSlide->addShape(createTextShape($run, $shapeHeight, $shapeWidth, $defaultMargin, $shapeYPosition));
                    $shapeYPosition += $shapeHeight + 10; 
                    break;
                case 'text':
                    $run->setText($itemContent); $run->getFont()->setSize(18);
                    $shapeHeight = 18 * 3; // Ruimte voor ~3 regels
                    $shape = createTextShape($run, $shapeHeight, $shapeWidth, $defaultMargin, $shapeYPosition);
                    $shape->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $currentSlide->addShape($shape);
                    $shapeYPosition += $shapeHeight + 5;
                    break;
                case 'list':
                    if (is_array($itemContent) && !empty($itemContent)) {
                         $listShape = new RichText(); $listShape->setWidth($shapeWidth);
                         $listShape->setOffsetX($defaultMargin); $listShape->setOffsetY($shapeYPosition);
                         $estimatedHeight = 0;
                         foreach ($itemContent as $listItem) {
                              $p = $listShape->createParagraph(); $p->getBulletStyle()->setBulletType(Bullet::TYPE_BULLET);
                              $runItem = $p->createRun($listItem); $runItem->getFont()->setSize(18);
                              $estimatedHeight += 25; 
                         }
                         $listShape->setHeight(max(50, $estimatedHeight));
                         $currentSlide->addShape($listShape);
                         $shapeYPosition += $listShape->getHeight() + 5;
                    }
                    break;
                case 'section_start': 
                     $currentSlide = $presentation->createSlide(); $shapeYPosition = $defaultMargin;
                     $run->setText($itemContent); $fontSize = match($itemLevel) { 1 => 44, 2 => 32, default => 28 };
                     $run->getFont()->setSize($fontSize)->setBold(true);
                     $shapeHeight = $fontSize * 1.5; 
                     $currentSlide->addShape(createTextShape($run, $shapeHeight, $shapeWidth, $defaultMargin, $shapeYPosition));
                     $shapeYPosition += $shapeHeight + 10; 
                     break;
            }
            if ($shapeYPosition > ($slideHeight - 100)) {
                 $currentSlide = $presentation->createSlide(); $shapeYPosition = $defaultMargin;
            }
        }
    }

    // 4. Genereer unieke bestandsnaam en pad
    $filename = 'presentatie_' . time() . '_' . uniqid() . '.pptx';
    $filePathAbsolute = $uploadDirAbsolute . '/' . $filename;
    $fileUrlRelative = $uploadDirRelative . '/' . $filename;

    // 5. Sla de presentatie op
    $writer = \PhpOffice\PhpPresentation\IOFactory::createWriter($presentation, 'PowerPoint2007');
    $writer->save($filePathAbsolute);

    // 6. Stuur de download URL terug
    http_response_code(200); 
    echo json_encode([
        'status' => 'success',
        'message' => 'Presentatie succesvol gegenereerd!', 
        'downloadUrl' => asset_url($fileUrlRelative) // Gebruik asset_url
    ]);
    exit;

} catch (Exception $e) {
    // Error handling (cURL, JSON decode, of PHPPresentation fouten)
    http_response_code(500); 
    echo json_encode(['status' => 'error', 'message' => 'Fout tijdens genereren presentatie: ' . $e->getMessage()]);
    ErrorHandler::getInstance()->logError('SlimmerPresenteren Fout', ['error' => $e->getMessage()]); // Algemenere log entry
    exit;
}

?> 