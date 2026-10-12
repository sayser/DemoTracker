#!/usr/bin/env php
<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

require dirname(__DIR__) . '/src/bootstrap.php';

$username = $argv[1] ?? '';
$email = $argv[2] ?? '';
$password = $argv[3] ?? '';

if ($username === '' || $email === '' || $password === '') {
    fwrite(STDERR, "Usage: php bin/create-admin.php <username> <email> <password>\n");
    exit(1);
}

try {
    $id = Auth::createUser($username, $email, $password, 'admin');
    fwrite(STDOUT, "Created admin user id={$id} username={$username}\n");
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
