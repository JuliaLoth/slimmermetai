<?php

namespace App\Application\Service;

use App\Infrastructure\Config\Config;
use finfo;
use Exception;

/**
 * Moderne UploadService met dependency injection
 * Vervangt de globale variabelen uit upload.php
 */
class UploadService
{
    private Config $config;
    private array $uploadDirectories;
    private array $allowedMimes;
    private array $sizeLimits;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->initializeConfiguration();
    }

    /**
     * Initialize upload configuration from Config service
     */
    private function initializeConfiguration(): void
    {
        $baseUploadPath = $this->config->get('upload_base_path', dirname(dirname(dirname(__DIR__))) . '/uploads/');

        $this->uploadDirectories = [
            'profile_pictures' => $baseUploadPath . 'profile_pictures/',
            'documents' => $baseUploadPath . 'documents/',
        ];

        $this->allowedMimes = [
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

        $this->sizeLimits = [
            'profile_pictures' => $this->config->get('max_profile_picture_size', 2 * 1024 * 1024), // 2MB
            'documents' => $this->config->get('max_document_size', 10 * 1024 * 1024) // 10MB
        ];
    }

    /**
     * Upload een bestand veilig
     */
    public function uploadFile(array $file, string $type, string $customFilename = null): array
    {
        // Valideer bestandstype
        if (!isset($this->uploadDirectories[$type])) {
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
            $errorMessage = $this->getUploadErrorMessage($file['error']);
            return [
                'success' => false,
                'error' => $errorMessage
            ];
        }

        // Controleer bestandsgrootte
        if ($file['size'] > $this->sizeLimits[$type]) {
            return [
                'success' => false,
                'error' => 'Bestand te groot (maximum: ' . $this->formatFileSize($this->sizeLimits[$type]) . ')'
            ];
        }

        // Detecteer MIME type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        // Controleer of MIME type is toegestaan
        if (!isset($this->allowedMimes[$type][$mimeType])) {
            return [
                'success' => false,
                'error' => 'Bestandstype niet toegestaan'
            ];
        }

        // Maak veilige bestandsnaam
        $extension = $this->allowedMimes[$type][$mimeType];
        $filename = $this->generateSafeFilename($customFilename, $extension);

        // Controleer of upload directory bestaat
        $uploadDir = $this->uploadDirectories[$type];
        if (!$this->ensureDirectoryExists($uploadDir)) {
            return [
                'success' => false,
                'error' => 'Kan upload directory niet aanmaken'
            ];
        }

        // Stel het volledige bestandspad samen
        $filepath = $uploadDir . $filename;

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
        if (strpos($mimeType, 'image/') === 0 && $mimeType !== 'image/gif') {
            $this->optimizeImage($filepath, $mimeType, $type);
        }

        // Bouw pad op voor database (relatief aan uploads directory)
        $dbPath = $type . '/' . $filename;

        return [
            'success' => true,
            'filename' => $filename,
            'mime_type' => $mimeType,
            'size' => $file['size'],
            'path' => $dbPath,
            'full_path' => $filepath
        ];
    }

    /**
     * Verwijder een geüpload bestand
     */
    public function deleteUploadedFile(string $path): bool
    {
        // Controleer of het pad geldig is
        $parts = explode('/', $path, 2);
        if (count($parts) !== 2 || !isset($this->uploadDirectories[$parts[0]])) {
            return false;
        }

        $type = $parts[0];
        $filename = $parts[1];

        // Controleer op directory traversal aanvallen
        if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
            return false;
        }

        // Stel het volledige bestandspad samen
        $filepath = $this->uploadDirectories[$type] . $filename;

        // Controleer of het bestand bestaat
        if (!file_exists($filepath)) {
            return false;
        }

        // Verwijder het bestand
        return unlink($filepath);
    }

    /**
     * Krijg het publieke URL pad voor een geüpload bestand
     */
    public function getUploadUrl(string $path): string
    {
        if (empty($path)) {
            return '';
        }

        $baseUrl = rtrim($this->config->get('site_url', ''), '/');
        return $baseUrl . '/uploads/' . $path;
    }

    /**
     * Generate safe filename
     */
    private function generateSafeFilename(?string $customFilename, string $extension): string
    {
        if ($customFilename) {
            // Verwijder ongeldige karakters uit custom filename
            $filename = preg_replace('/[^a-zA-Z0-9_-]/', '', $customFilename);
        } else {
            // Genereer unieke bestandsnaam
            $filename = uniqid('', true);
        }

        // Voeg timestamp toe om bestandsnamen uniek te maken
        return $filename . '_' . time() . '.' . $extension;
    }

    /**
     * Ensure directory exists
     */
    private function ensureDirectoryExists(string $directory): bool
    {
        if (!is_dir($directory)) {
            return mkdir($directory, 0755, true);
        }
        return true;
    }

    /**
     * Optimaliseer geüploade afbeelding
     */
    private function optimizeImage(string $filepath, string $mimeType, string $type): void
    {
        // Sla optimalisatie over als de GD extensie niet is geladen
        if (!extension_loaded('gd')) {
            return;
        }

        // Bepaal maximale afmetingen op basis van uploadtype
        $maxWidth = 1200;
        $maxHeight = 1200;

        if ($type === 'profile_pictures') {
            $maxWidth = 500;
            $maxHeight = 500;
        }

        // Laad de afbeelding op basis van het MIME type
        $image = match ($mimeType) {
            'image/jpeg' => imagecreatefromjpeg($filepath),
            'image/png' => imagecreatefrompng($filepath),
            'image/webp' => imagecreatefromwebp($filepath),
            default => null
        };

        if (!$image) {
            return; // Kon afbeelding niet laden
        }

        // Bepaal huidige afmetingen
        $width = imagesx($image);
        $height = imagesy($image);

        // Controleer of resize nodig is
        if ($width > $maxWidth || $height > $maxHeight) {
            $newImage = $this->resizeImage($image, $width, $height, $maxWidth, $maxHeight, $mimeType);
            imagedestroy($image);
            $image = $newImage;
        }

        // Sla de afbeelding opnieuw op
        match ($mimeType) {
            'image/jpeg' => imagejpeg($image, $filepath, 85),
            'image/png' => imagepng($image, $filepath, 8),
            'image/webp' => imagewebp($image, $filepath, 85),
            default => null
        };

        // Ruim geheugen op
        imagedestroy($image);
    }

    /**
     * Resize image while maintaining aspect ratio
     */
    private function resizeImage(\GdImage $image, int $width, int $height, int $maxWidth, int $maxHeight, string $mimeType): \GdImage
    {
        // Bereken nieuwe afmetingen met behoud van aspect ratio
        if ($width > $height) {
            $newWidth = $maxWidth;
            $newHeight = floor($height * ($maxWidth / $width));
        } else {
            $newHeight = $maxHeight;
            $newWidth = floor($width * ($maxHeight / $height));
        }

        // Maak nieuw canvas
        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        // Behoud transparantie voor PNG
        if ($mimeType === 'image/png') {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

        // Schaal de afbeelding
        imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        return $newImage;
    }

    /**
     * Get readable upload error message
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE => 'Bestand te groot (overschrijdt upload_max_filesize directive in php.ini)',
            UPLOAD_ERR_FORM_SIZE => 'Bestand te groot (overschrijdt MAX_FILE_SIZE directive in HTML form)',
            UPLOAD_ERR_PARTIAL => 'Bestand slechts gedeeltelijk geüpload',
            UPLOAD_ERR_NO_FILE => 'Geen bestand geüpload',
            UPLOAD_ERR_NO_TMP_DIR => 'Geen tijdelijke map beschikbaar',
            UPLOAD_ERR_CANT_WRITE => 'Kan bestand niet naar schijf schrijven',
            UPLOAD_ERR_EXTENSION => 'Bestandsupload gestopt door extensie',
            default => 'Onbekende uploadfout'
        };
    }

    /**
     * Format file size to readable form
     */
    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
