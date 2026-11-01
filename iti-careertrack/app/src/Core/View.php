<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    /** Render views/{$view}.php inside the main layout (or 'auth' layout). */
    public static function render(string $view, array $data = [], string $layout = 'app'): void
    {
        $content = self::partial($view, $data);
        echo self::partial('layouts/' . $layout, $data + ['content' => $content]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    public static function partial(string $view, array $data = []): string
    {
        $file = __DIR__ . '/../../views/' . $view . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("View not found: $view");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }

    public static function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
