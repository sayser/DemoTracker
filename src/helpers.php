<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function flashes(): array
{
    $items = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return is_array($items) ? $items : [];
}

function redirect(string $path, int $code = 302): never
{
    $path = url(Security::internalPath($path));
    header('Location: ' . $path, true, $code);
    exit;
}

function url(string $path): string
{
    $path = '/' . ltrim($path, '/');
    if ($path === '/index.php') {
        $path = '/';
    }
    $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $base = rtrim(str_replace('\\', '/', dirname($script)), '/');
    if ($base === '/' || $base === '\\' || $base === '.') {
        $base = '';
    }
    if ($path === '/') {
        return $base === '' ? '/' : $base . '/';
    }
    return $base . $path;
}

function current_path(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $base = rtrim(str_replace('\\', '/', dirname($script)), '/');
    if ($base && $base !== '/' && str_starts_with($uri, $base)) {
        $uri = substr($uri, strlen($base)) ?: '/';
    }
    if ($uri !== '/' ) {
        $uri = rtrim($uri, '/');
    }
    return $uri === '' ? '/' : $uri;
}

function request_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function post_string(string $key, string $default = ''): string
{
    $v = $_POST[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function post_int(string $key, int $default = 0): int
{
    $v = $_POST[$key] ?? $default;
    return filter_var($v, FILTER_VALIDATE_INT) !== false ? (int) $v : $default;
}

function query_int(string $key, int $default = 0): int
{
    $v = $_GET[$key] ?? $default;
    return filter_var($v, FILTER_VALIDATE_INT) !== false ? (int) $v : $default;
}

function client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return is_string($ip) ? $ip : '0.0.0.0';
}

function role_label(string $role): string
{
    return match ($role) {
        'admin' => 'Admin',
        'standard' => 'Standard',
        'view' => 'View only',
        default => $role,
    };
}

function can_reserve(?array $user): bool
{
    return $user !== null && in_array($user['role'], ['admin', 'standard'], true) && (int) $user['active'] === 1;
}

function is_admin(?array $user): bool
{
    return $user !== null && $user['role'] === 'admin' && (int) $user['active'] === 1;
}

function vm_color_class(int $vmId): string
{
    return 'vm-hue-' . ((($vmId - 1) % 12) + 1);
}

function duration_label(int $amount, string $unit): string
{
    $unit = rtrim($unit, 's');
    $plural = $amount === 1 ? $unit : $unit . 's';
    return $amount . ' ' . $plural;
}

function add_duration(DateTimeImmutable $startUtc, int $amount, string $unit): DateTimeImmutable
{
    $unit = strtolower($unit);
    $spec = match ($unit) {
        'hour', 'hours' => 'PT' . $amount . 'H',
        'day', 'days' => 'P' . $amount . 'D',
        'week', 'weeks' => 'P' . ($amount * 7) . 'D',
        default => throw new InvalidArgumentException('Duration must be hours, days, or weeks.'),
    };
    if ($amount > 21 && in_array($unit, ['day', 'days'], true)) {
        throw new InvalidArgumentException('Reservations cannot be longer than 3 weeks.');
    }
    if ($amount > 3 && in_array($unit, ['week', 'weeks'], true)) {
        throw new InvalidArgumentException('Reservations cannot be longer than 3 weeks.');
    }
    if ($amount > 504 && in_array($unit, ['hour', 'hours'], true)) {
        throw new InvalidArgumentException('Reservations cannot be longer than 3 weeks.');
    }
    $startApp = $startUtc->setTimezone(Timezone::appTz());
    return $startApp->add(new DateInterval($spec))->setTimezone(Timezone::utcTz());
}

function render(string $template, array $data = []): void
{
    global $config;
    $data['appTitle'] = $config['app']['title'];
    $data['user'] = $data['user'] ?? Auth::user();
    $data['flashes'] = flashes();
    $data['csrfField'] = Csrf::field();
    extract($data, EXTR_SKIP);
    $viewFile = Security::allowedView($template);
    ob_start();
    require $viewFile;
    $content = ob_get_clean();
    require VIEWS_DIR . '/layout.php';
}

function require_login(): array
{
    $user = Auth::user();
    if ($user === null) {
        redirect('/login');
    }
    return $user;
}

function require_admin(): array
{
    $user = require_login();
    if (!is_admin($user)) {
        flash('error', 'You do not have permission to do that.');
        redirect('/');
    }
    return $user;
}

function require_can_reserve(): array
{
    $user = require_login();
    if (!can_reserve($user)) {
        flash('error', 'View-only accounts cannot create or change reservations.');
        redirect('/');
    }
    return $user;
}
