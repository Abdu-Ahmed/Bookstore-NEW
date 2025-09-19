<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Simple view renderer with layout support.
 */
final class View
{
    private string $viewsPath;
    private string $ext = '.php';

    public function __construct(string $viewsPath)
    {
        $this->viewsPath = rtrim($viewsPath, DIRECTORY_SEPARATOR);
    }

    /**
     * Render template and return HTML string.
     *
     * @param string               $template Template name relative to views dir (e.g. "user/account")
     * @param array<string,mixed>  $params
     */
    public function render(string $template, array $params = []): string
    {
        $path = $this->resolvePath($template);
        if (!is_file($path)) {
            throw new \RuntimeException("View not found: {$path}");
        }

        extract($params, EXTR_SKIP);

        $escape = function (mixed $v): string {
            if ($v === null) {
                return '';
            }
            return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        };

        ob_start();
        require $path;
        $content = (string) ob_get_clean();

        if (!empty($params['layout'])) {
            $layoutPath = $this->resolvePath((string) $params['layout']);
            if (!is_file($layoutPath)) {
                throw new \RuntimeException("Layout not found: {$layoutPath}");
            }
            ob_start();
            // $content and $escape are available in layout
            require $layoutPath;
            return (string) ob_get_clean();
        }

        return $content;
    }

    private function resolvePath(string $template): string
    {
        $template = ltrim($template, '/\\');
        return $this->viewsPath . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $template) . $this->ext;
    }
}
