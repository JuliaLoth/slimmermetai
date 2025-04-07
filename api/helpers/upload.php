<?php
/**
 * Upload helper - SlimmerMetAI.com
 * 
 * Functies voor het veilig uploaden en verwerken van bestanden
 */

// Defineer SITE_ROOT als dat nog niet is gebeurd
if (!defined('SITE_ROOT')) {
    define('SITE_ROOT', dirname(dirname(__DIR__)));
}

// Configuratie
$UPLOAD_DIRECTORIES = [
    'profile_pictures' => dirname(SITE_ROOT) . '/uploads/profile_pictures/',
    'documents' => dirname(SITE_ROOT) . '/uploads/documents/',
];

// Toegestane MIME types per categorie
$ALLOWED_MIMES = [
    'profile_pictures' => [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ],
    'documents' => [
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'text/plain' => 'txt'
    ]
];

// Grootte limieten in bytes
$SIZE_LIMITS = [
    'profile_pictures' => 2 * 1024 * 1024, // 2MB
    'documents' => 10 * 1024 * 1024 // 10MB
];

/**
 * Uploadt een bestand veilig
 * 
 * @param array $file $_FILES array element
 * @param string $type Type upload (profile_pictures, documents, etc)
 * @param string $custom_filename Aangepaste bestandsnaam (optioneel)
 * @return array Resultaat met status en bestandspad of foutmelding
 */
function upload_file($file, $type, $custom_filename = null) {
    global $UPLOAD_DIRECTORIES, $ALLOWED_MIMES, $SIZE_LIMITS;
    
    // Valideer bestandstype
    if (!isset($UPLOAD_DIRECTORIES[$type])) {
        return [
            'success' => false,
            'error' => 'Ongeldig uploadtype'
        ];
    }
    
    // Controleer of het bestand succesvol is geüpload
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return [
            'success' => false,
            'error' => 'Geen bestand geüpload of uploadfout'
        ];
    }
    
    // Controleer op uploadfouten
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_message = get_upload_error_message($file['error']);
        return [
            'success' => false,
            'error' => $error_message
        ];
    }
    
    // Controleer bestandsgrootte
    if ($file['size'] > $SIZE_LIMITS[$type]) {
        return [
            'success' => false,
            'error' => 'Bestand te groot (maximum: ' . format_file_size($SIZE_LIMITS[$type]) . ')'
        ];
    }
    
    // Detecteer MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file['tmp_name']);
    
    // Controleer of MIME type is toegestaan
    if (!isset($ALLOWED_MIMES[$type][$mime_type])) {
        return [
            'success' => false,
            'error' => 'Bestandstype niet toegestaan'
        ];
    }
    
    // Maak veilige bestandsnaam
    $extension = $ALLOWED_MIMES[$type][$mime_type];
    
    if ($custom_filename) {
        // Verwijder ongeldige karakters uit custom filename
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '', $custom_filename);
    } else {
        // Genereer unieke bestandsnaam
        $filename = uniqid('', true);
    }
    
    // Voeg timestamp toe om bestandsnamen uniek te maken
    $filename = $filename . '_' . time() . '.' . $extension;
    
    // Controleer of upload directory bestaat, zo niet, maak het aan
    $upload_dir = $UPLOAD_DIRECTORIES[$type];
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            return [
                'success' => false,
                'error' => 'Kan upload directory niet aanmaken'
            ];
        }
    }
    
    // Stel het volledige bestandspad samen
    $filepath = $upload_dir . $filename;
    
    // Verplaats geüpload bestand
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'success' => false,
            'error' => 'Kan bestand niet opslaan'
        ];
    }
    
    // Pas bestandsrechten aan
    chmod($filepath, 0644);
    
    // Als dit een afbeelding is, optimaliseer het
    if (strpos($mime_type, 'image/') === 0 && $mime_type !== 'image/gif') {
        optimize_image($filepath, $mime_type, $type);
    }
    
    // Bouw pad op voor database (relatief aan uploads directory)
    $db_path = $type . '/' . $filename;
    
    return [
        'success' => true,
        'filename' => $filename,
        'mime_type' => $mime_type,
        'size' => $file['size'],
        'path' => $db_path,
        'full_path' => $filepath
    ];
}

/**
 * Optimaliseer geüploade afbeelding
 * 
 * @param string $filepath Volledig pad naar de afbeelding
 * @param string $mime_type MIME type van de afbeelding
 * @param string $type Type upload (profile_pictures, etc)
 * @return void
 */
function optimize_image($filepath, $mime_type, $type) {
    // Sla optimalisatie over als de GD extensie niet is geladen
    if (!extension_loaded('gd')) {
        return;
    }
    
    // Bepaal maximale afmetingen op basis van uploadtype
    $max_width = 1200;
    $max_height = 1200;
    
    if ($type === 'profile_pictures') {
        $max_width = 500;
        $max_height = 500;
    }
    
    // Laad de afbeelding op basis van het MIME type
    $image = null;
    switch ($mime_type) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($filepath);
            break;
        case 'image/png':
            $image = imagecreatefrompng($filepath);
            break;
        case 'image/webp':
            $image = imagecreatefromwebp($filepath);
            break;
        default:
            return; // Ondersteund type niet gevonden
    }
    
    if (!$image) {
        return; // Kon afbeelding niet laden
    }
    
    // Bepaal huidige afmetingen
    $width = imagesx($image);
    $height = imagesy($image);
    
    // Controleer of resize nodig is
    if ($width > $max_width || $height > $max_height) {
        // Bereken nieuwe afmetingen met behoud van aspect ratio
        if ($width > $height) {
            $new_width = $max_width;
            $new_height = floor($height * ($max_width / $width));
        } else {
            $new_height = $max_height;
            $new_width = floor($width * ($max_height / $height));
        }
        
        // Maak nieuw canvas
        $new_image = imagecreatetruecolor($new_width, $new_height);
        
        // Behoud transparantie voor PNG
        if ($mime_type === 'image/png') {
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
            imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
        }
        
        // Schaal de afbeelding
        imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        imagedestroy($image);
        $image = $new_image;
    }
    
    // Sla de afbeelding opnieuw op
    switch ($mime_type) {
        case 'image/jpeg':
            imagejpeg($image, $filepath, 85); // 85% kwaliteit
            break;
        case 'image/png':
            imagepng($image, $filepath, 8); // Compressie niveau 8 (0-9)
            break;
        case 'image/webp':
            imagewebp($image, $filepath, 85); // 85% kwaliteit
            break;
    }
    
    // Ruim geheugen op
    imagedestroy($image);
}

/**
 * Verwijder een geüpload bestand
 * 
 * @param string $path Relatief pad van het bestand (zoals opgeslagen in database)
 * @return boolean Success indicator
 */
function delete_uploaded_file($path) {
    global $UPLOAD_DIRECTORIES;
    
    // Controleer of het pad geldig is
    $parts = explode('/', $path, 2);
    if (count($parts) !== 2 || !isset($UPLOAD_DIRECTORIES[$parts[0]])) {
        return false;
    }
    
    $type = $parts[0];
    $filename = $parts[1];
    
    // Controleer op directory traversal aanvallen
    if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
        return false;
    }
    
    // Stel het volledige bestandspad samen
    $filepath = $UPLOAD_DIRECTORIES[$type] . $filename;
    
    // Controleer of het bestand bestaat
    if (!file_exists($filepath)) {
        return false;
    }
    
    // Verwijder het bestand
    return unlink($filepath);
}

/**
 * Krijg een leesbare foutmelding voor uploadfouten
 * 
 * @param int $error_code Upload error code
 * @return string Leesbare foutmelding
 */
function get_upload_error_message($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
            return 'Bestand te groot (overschrijdt upload_max_filesize directive in php.ini)';
        case UPLOAD_ERR_FORM_SIZE:
            return 'Bestand te groot (overschrijdt MAX_FILE_SIZE directive in HTML form)';
        case UPLOAD_ERR_PARTIAL:
            return 'Bestand slechts gedeeltelijk geüpload';
        case UPLOAD_ERR_NO_FILE:
            return 'Geen bestand geüpload';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Geen tijdelijke map beschikbaar';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Kan bestand niet naar schijf schrijven';
        case UPLOAD_ERR_EXTENSION:
            return 'Bestandsupload gestopt door extensie';
        default:
            return 'Onbekende uploadfout';
    }
}

/**
 * Formatteer bestandsgrootte naar leesbare vorm
 * 
 * @param int $bytes Bestandsgrootte in bytes
 * @return string Geformatteerde bestandsgrootte
 */
function format_file_size($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Krijg het publieke URL pad voor een geüpload bestand
 * 
 * @param string $path Relatief pad van het bestand (zoals opgeslagen in database)
 * @return string Publieke URL van het bestand
 */
function get_upload_url($path) {
    global $config;
    
    if (empty($path)) {
        return '';
    }
    
    $base_url = isset($config['site_url']) ? rtrim($config['site_url'], '/') : '';
    return $base_url . '/uploads/' . $path;
}
?> 