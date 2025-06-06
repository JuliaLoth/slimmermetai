<?php

namespace App\Infrastructure\View;

class View
{
    private const BASE_PATH = __DIR__ . '/../../../resources/views/';
/**
     * Render een view met optionele layout.
     */
    public static function render(string $template, array $data = [], ?string $layout = 'layout/main'): void
    {
        echo self::renderToString($template, $data, $layout);
    }

    /**
     * Render alleen een template (geen layout) en geef de output-string terug.
     */
    private static function capture(string $template, array $data = []): string
    {
        ob_start();
        self::printTemplate($template, $data);
        return ob_get_clean();
    }

    public static function renderToString(string $template, array $data = [], ?string $layout = 'layout/main'): string
    {
        // Capture hoofdtemplate
        $content = self::capture($template, $data);
        if ($layout) {
            $data['content'] = $content;
            ob_start();
            self::printTemplate($layout, $data);
            return ob_get_clean();
        }
        return $content;
    }

    private static function printTemplate(string $template, array $data): void
    {
        $path = self::BASE_PATH . $template . '.php';
        if (!file_exists($path)) {
            http_response_code(500);
            echo "View {$template} not found";
            return;
        }
        extract($data, EXTR_SKIP);
        require $path;
    }
}
