<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Predis\Client;

function redisClient(): Client
{
    return new Client([
        'scheme' => $_ENV['REDIS_SCHEME'] ?? 'tcp',
        'host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
        'port' => (int) ($_ENV['REDIS_PORT'] ?? 6379),
        'password' => $_ENV['REDIS_PASSWORD'] ?: null,
    ]);
}
