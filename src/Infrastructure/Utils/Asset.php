<?php

namespace App\Infrastructure\Utils;

/**
 * Hulpmethode om versleutelde Vite-bundles (met hash) op te halen uit het manifest.
 */
final class Asset
{
    private const MANIFEST_PATH = __DIR__ . '/../../../public_html/assets/js/.vite/manifest.json';
    private static ?array $manifest = null;
    private static array $nameMap = [];
// name -> entry key
    private static array $cssMap = []; // name -> css file

    private function __construct()
    {
    }

    /**
     * Geef de publieke URL voor een Vite-entry zoals 'main' of 'bundle-main.js'.
     * In views te gebruiken als: Asset::url('main') of Asset::url('bundle-main.js')
     */
    public static function url(string $asset): string
    {
        self::loadManifest();
// Development fallback: alleen als expliciet APP_ENV=local/development
        $isDevelopment = (getenv('APP_ENV') === 'local' || getenv('APP_ENV') === 'development');
// Eerst proberen via name mapping
        if (isset(self::$nameMap[$asset])) {
            $entryKey = self::$nameMap[$asset];
            $entry = self::$manifest[$entryKey];
            $file = $entry['file'];
// Strip 'assets/' prefix als aanwezig (Vite dubbele nesting)
            if (str_starts_with($file, 'assets/')) {
                $file = substr($file, strlen('assets/'));
            }

            // Development workaround: server assets via front controller
            if ($isDevelopment) {
                return '/dev-asset/' . $file;
            }

            return '/assets/js/' . $file;
        }

        // Fallback: direct zoeken in manifest keys
        if (isset(self::$manifest[$asset])) {
            $file = self::$manifest[$asset]['file'] ?? $asset;
            if (str_starts_with($file, 'assets/')) {
                $file = substr($file, strlen('assets/'));
            }

            if ($isDevelopment) {
                return '/dev-asset/' . $file;
            }

            return '/assets/js/' . $file;
        }

        // Laatste fallback: als asset al 'bundle-' bevat, direct gebruiken
        if (str_contains($asset, 'bundle-') || str_contains($asset, 'chunk-')) {
            if ($isDevelopment) {
                return '/dev-asset/' . $asset;
            }
            return '/assets/js/' . $asset;
        }

        // Als niets werkt, probeer met bundle- prefix
        $finalAsset = 'bundle-' . $asset;
        if ($isDevelopment) {
            return '/dev-asset/' . $finalAsset;
        }
        return '/assets/js/' . $finalAsset;
    }

    /**
     * Retourneer de URL van de eerste CSS file behorend bij een entry, of null.
     */
    public static function css(string $asset): ?string
    {
        self::loadManifest();
        $isDevelopment = (getenv('APP_ENV') === 'local' || getenv('APP_ENV') === 'development');
        if (isset(self::$cssMap[$asset])) {
            $file = self::$cssMap[$asset];
            if (str_starts_with($file, 'assets/')) {
                $file = substr($file, strlen('assets/'));
            }

            if ($isDevelopment) {
                return '/dev-asset/' . $file;
            }

            return '/assets/js/' . $file;
        }

        return null;
    }

    private static function loadManifest(): void
    {
        if (self::$manifest !== null) {
            return;
// Al geladen
        }

        self::$manifest = [];
        self::$nameMap = [];
        self::$cssMap = [];
        if (!file_exists(self::MANIFEST_PATH)) {
            return;
        }

        $json = json_decode(file_get_contents(self::MANIFEST_PATH), true);
        if (!is_array($json)) {
            return;
        }

        self::$manifest = $json;
// Bouw name mapping en CSS mapping
        foreach ($json as $entryKey => $entry) {
            if (isset($entry['name'])) {
                self::$nameMap[$entry['name']] = $entryKey;
            // CSS mapping
                if (isset($entry['css'][0])) {
                    self::$cssMap[$entry['name']] = $entry['css'][0];
                }
            }
        }
    }
}
