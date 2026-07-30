<?php
declare(strict_types=1);

/**
 * Application container: config, base-path detection, view rendering.
 */
final class App
{
    private static array $config = [];

    /** Accent colours for the four ฝ่าย — mirror --c1..--c4 in app.css. */
    public const DIVISION_COLORS = ['var(--c1)', 'var(--c2)', 'var(--c3)', 'var(--c4)', 'var(--c5)'];

    public const STATUSES = ['เผยแพร่', 'ฉบับร่าง'];
    public const ROLES    = ['ผู้ดูแลระบบ', 'เจ้าหน้าที่'];

    public static function boot(): void
    {
        $path = dirname(__DIR__) . '/config/config.php';
        if (!is_file($path)) {
            http_response_code(500);
            header('Content-Type: text/html; charset=utf-8');
            exit('ยังไม่ได้ตั้งค่าระบบ — เปิด <a href="install.php">install.php</a> เพื่อติดตั้ง');
        }
        self::$config = require $path;

        if (self::config('app')['debug'] ?? false) {
            ini_set('display_errors', '1');
            error_reporting(E_ALL);
        }
    }

    /** @return mixed */
    public static function config(string $section)
    {
        return self::$config[$section] ?? null;
    }

    public static function name(): string
    {
        return self::config('app')['name'] ?? 'คู่มือการปฏิบัติงาน';
    }

    /** Base URL path the app is mounted at, e.g. "/rvc.man". */
    public static function basePath(): string
    {
        $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        return rtrim($dir, '/');
    }

    public static function url(string $path = ''): string
    {
        return self::basePath() . '/' . ltrim($path, '/');
    }

    public static function asset(string $path): string
    {
        return self::basePath() . '/assets/' . ltrim($path, '/');
    }

    /**
     * Render a view inside views/layout.php and echo it.
     *
     * @param array<string,mixed> $vars
     * @param array<string,mixed> $layout title, section, ...
     */
    public static function render(string $view, array $vars = [], array $layout = []): void
    {
        $layout['content'] = self::partial($view, $vars);
        echo self::partial('layout', $layout);
    }

    /** Render a view to a string. */
    public static function partial(string $view, array $vars = []): string
    {
        $file = dirname(__DIR__) . '/views/' . $view . '.php';
        if (!is_file($file)) {
            throw new RuntimeException("View not found: {$view}");
        }
        extract($vars, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }
}
