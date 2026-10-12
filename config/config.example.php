<?php

declare(strict_types=1);

return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'demo_reservation',
        'user' => 'demo_reserv',
        'pass' => 'change_me',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'title' => 'Demo Reservation',
        'timezone' => 'America/New_York',
        'base_url' => 'https://demo-reserv.example',
        'from_email' => 'demo-reserv@example.com',
        'from_name' => 'Demo Reservation',
        'max_duration_days' => 21,
        'session_name' => 'demo_reserv_sess',
        'https' => true,
        'login_max_attempts' => 5,
        'login_window_minutes' => 15,
    ],
];
