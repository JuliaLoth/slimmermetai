<?php
namespace App\Infrastructure\Utils;

use App\Infrastructure\Config\Config;

/**
 * FileValidator – injectable utility met instance-methoden.
 */
final class FileValidator
{
    private array $allowedTypes;
    private int $maxUpload;

    public function __construct(Config $config)
    {
        $this->allowedTypes = $config->get('allowed_file_types');
        $this->maxUpload    = (int)$config->get('max_upload_size');
    }

    /**
     * Bestandsnaam saneren.
     */
    public function sanitizeFileName(string $fileName): string
    {
        $fileName = preg_replace('/[^a-zA-Z0-9.\-_]/', '_', $fileName);
        return preg_replace('/_+/', '_', $fileName);
    }

    /**
     * Extensie valideert tegen lijst.
     */
    public function validateFileType(array $file): bool
    {
        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        return in_array($ext, $this->allowedTypes, true);
    }

    /**
     * Grootte validatie.
     */
    public function validateFileSize(array $file): bool
    {
        return ($file['size'] ?? 0) <= $this->maxUpload;
    }
} 