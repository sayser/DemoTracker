#!/usr/bin/env php
<?php

declare(strict_types=1);

// Optional. The website already expires due reservations and sends mail
// on each page load. Use this only if you want to run the same job by hand.

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
