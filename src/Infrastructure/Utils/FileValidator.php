<?php
namespace App\Infrastructure\Utils;

use App\Infrastructure\Config\Config;

/**
 * Hulpmethoden voor bestandsvalidatie en -sanitatie.
 */
final class FileValidator
{
    private function __construct() {}

    /**
     * Verwijder onveilige karakters en dubbele underscores uit een bestandsnaam.
     */
    public static function sanitizeFileName(string $fileName): string
    {
        // Verwijder alles behalve letters, cijfers, punt en koppelteken/underscore
        $fileName = preg_replace('/[^a-zA-Z0-9.\-_]/', '_', $fileName);
        // Vervang meerdere underscores door één
        return preg_replace('/_+/', '_', $fileName);
    }

    /**
     * Controleer of een upload een toegestane extensie heeft.
     * @param array{ name:string } $file  $_FILES-item
     */
    public static function validateFileType(array $file): bool
    {
        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        $allowed = Config::getInstance()->get('allowed_file_types');
        return in_array($ext, $allowed, true);
    }

    /**
     * Controleer of de bestandsgrootte onder de limiet blijft.
     * @param array{ size:int } $file  $_FILES-item
     */
    public static function validateFileSize(array $file): bool
    {
        $max = Config::getInstance()->get('max_upload_size');
        return ($file['size'] ?? 0) <= $max;
    }
} 