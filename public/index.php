<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';
require dirname(__DIR__) . '/src/App.php';

try {
    App::dispatch();
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo 'The application encountered an error.';
}
