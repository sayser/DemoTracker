#!/usr/bin/env php
<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

require dirname(__DIR__) . '/src/bootstrap.php';

try {
    $n = ReservationService::expireDue();
    Auth::pruneLoginAttempts();
    fwrite(STDOUT, date('c') . " expired {$n} reservation(s)\n");
} catch (Throwable $e) {
    fwrite(STDERR, date('c') . ' ' . $e->getMessage() . "\n");
    exit(1);
}
