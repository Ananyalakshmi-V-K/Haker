<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/redis.php';
require_once __DIR__ . '/../utils/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$token = bearerToken();
if (!$token) {
    jsonResponse(['error' => 'Missing token'], 401);
}

$redis = redisClient();
$session = $redis->get("session:{$token}");

if (!$session) {
    jsonResponse(['error' => 'Session expired or invalid'], 401);
}

jsonResponse(['data' => ['valid' => true]]);
