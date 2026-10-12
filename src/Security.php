<?php

declare(strict_types=1);

final class Security
{
    private const PLACEHOLDERS = ['ENTER_SECURE_PASSWORD', 'change_me', ''];

    public static function assertConfig(array $config): void
    {
        $host = strtolower((string) ($config['db']['host'] ?? ''));
        if (!in_array($host, ['127.0.0.1', 'localhost'], true)) {
            self::fail('Database host must be 127.0.0.1 or localhost.');
        }
        $charset = strtolower((string) ($config['db']['charset'] ?? ''));
        if ($charset !== 'utf8mb4') {
            self::fail('Database charset must be utf8mb4.');
        }
        $pass = (string) ($config['db']['pass'] ?? '');
        if (in_array($pass, self::PLACEHOLDERS, true)) {
            self::fail('Replace ENTER_SECURE_PASSWORD in config/config.php before running the site.');
        }
        $from = (string) ($config['app']['from_email'] ?? '');
        if (filter_var($from, FILTER_VALIDATE_EMAIL) === false) {
            self::fail('app.from_email must be a valid email address.');
        }
    }

    public static function sendHeaders(bool $https): void
    {
        header('Content-Type: text/html; charset=UTF-8');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: no-referrer');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; font-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
        header('Cache-Control: no-store, no-cache, must-revalidate, private');
        header('Pragma: no-cache');
        if ($https) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }

    public static function requestIsHttps(array $config): bool
    {
        if (!empty($config['app']['https'])) {
            return true;
        }
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }
        if (!empty($config['app']['trust_proxy'])
            && isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
            && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        }
        return false;
    }

    public static function internalPath(string $path): string
    {
        $path = str_replace(["\r", "\n", "\0", '\\'], '', $path);
        if ($path === '' || !str_starts_with($path, '/') || str_starts_with($path, '//') || str_contains($path, '://')) {
            return '/';
        }
        return $path;
    }

    public static function oneLine(string $value): string
    {
        return str_replace(["\r", "\n", "\0"], '', $value);
    }

    public static function publicMessage(Throwable $e): string
    {
        if ($e instanceof PDOException || $e instanceof Error) {
            error_log($e->getMessage());
            return 'The request could not be completed. Try again or contact an administrator.';
        }
        if ($e instanceof InvalidArgumentException || $e instanceof RuntimeException) {
            return $e->getMessage();
        }
        error_log($e->getMessage());
        return 'The request could not be completed. Try again or contact an administrator.';
    }

    public static function allowedView(string $template): string
    {
        if (!preg_match('/^[a-z0-9_]+$/', $template)) {
            throw new RuntimeException('Invalid view.');
        }
        $file = VIEWS_DIR . DIRECTORY_SEPARATOR . $template . '.php';
        $real = realpath($file);
        $root = realpath(VIEWS_DIR);
        if ($real === false || $root === false || dirname($real) !== $root || !is_file($real)) {
            throw new RuntimeException('Invalid view.');
        }
        return $real;
    }

    public static function passwordAlgo(): string|int
    {
        if (defined('PASSWORD_ARGON2ID')) {
            return PASSWORD_ARGON2ID;
        }
        return PASSWORD_DEFAULT;
    }

    private static function fail(string $message): never
    {
        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, $message . "\n");
        } else {
            http_response_code(500);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'Application is not configured.';
        }
        exit(1);
    }
}
