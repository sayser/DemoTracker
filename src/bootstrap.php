<?php

declare(strict_types=1);

define('ROOT_DIR', dirname(__DIR__));
define('CONFIG_DIR', ROOT_DIR . '/config');
define('VIEWS_DIR', ROOT_DIR . '/views');
define('PUBLIC_DIR', ROOT_DIR . '/public');

$configFile = CONFIG_DIR . '/config.php';
if (!is_file($configFile)) {
    fwrite(STDERR, "Missing config/config.php. Copy config/config.example.php and edit credentials.\n");
    if (PHP_SAPI !== 'cli') {
        http_response_code(500);
        echo 'Application is not configured.';
    }
    exit(1);
}

/** @var array $config */
$config = require $configFile;

require ROOT_DIR . '/src/Database.php';
require ROOT_DIR . '/src/Timezone.php';
require ROOT_DIR . '/src/Csrf.php';
require ROOT_DIR . '/src/Http.php';
require ROOT_DIR . '/src/Auth.php';
require ROOT_DIR . '/src/Mailer.php';
require ROOT_DIR . '/src/WatchService.php';
require ROOT_DIR . '/src/ReservationService.php';
require ROOT_DIR . '/src/helpers.php';

date_default_timezone_set('UTC');

if (PHP_SAPI !== 'cli') {
    $https = !empty($config['app']['https'])
        || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    session_name((string) $config['app']['session_name']);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'secure' => $https,
        'samesite' => 'Lax',
    ]);
    session_start();
}
