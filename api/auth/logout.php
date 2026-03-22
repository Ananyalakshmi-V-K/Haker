<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/redis.php';
require_once __DIR__ . '/../utils/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$token = bearerToken();
if ($token) {
    $redis = redisClient();
    $redis->del(["session:{$token}"]);
}

jsonResponse(['data' => ['logged_out' => true]]);
