<?php

declare(strict_types=1);

define('ROOT_DIR', dirname(__DIR__));
define('CONFIG_DIR', ROOT_DIR . '/config');
define('VIEWS_DIR', ROOT_DIR . '/views');
define('PUBLIC_DIR', ROOT_DIR . '/public');

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

$configFile = CONFIG_DIR . '/config.php';
if (!is_file($configFile)) {
    fwrite(STDERR, "Missing config/config.php.\n");
    if (PHP_SAPI !== 'cli') {
        http_response_code(500);
        echo 'Application is not configured.';
    }
    exit(1);
}

/** @var array $config */
$config = require $configFile;

require ROOT_DIR . '/src/Security.php';
Security::assertConfig($config);

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
    $https = Security::requestIsHttps($config);
    Security::sendHeaders($https);

    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_trans_sid', '0');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Strict');

    $sessionName = (string) $config['app']['session_name'];
    if (!preg_match('/^[A-Za-z0-9_]{1,32}$/', $sessionName)) {
        $sessionName = 'demo_reserv_sess';
    }
    session_name($sessionName);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'secure' => $https,
        'samesite' => 'Strict',
    ]);
    session_start();
}
