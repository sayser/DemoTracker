<?php

declare(strict_types=1);

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function field(): string
    {
        $t = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="_csrf" value="' . $t . '">';
    }

    public static function verify(?string $token): bool
    {
        $session = $_SESSION['_csrf'] ?? '';
        return is_string($session) && is_string($token) && hash_equals($session, $token);
    }

    public static function requireValid(): void
    {
        $token = $_POST['_csrf'] ?? '';
        if (!self::verify(is_string($token) ? $token : null)) {
            http_response_code(400);
            flash('error', 'Your session expired. Please try again.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/');
        }
    }
}
