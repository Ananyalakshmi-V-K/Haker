<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/redis.php';
require_once __DIR__ . '/../config/mongo.php';
require_once __DIR__ . '/../utils/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$token = bearerToken();
if (!$token) {
    jsonResponse(['error' => 'Missing token'], 401);
}

$redis = redisClient();
$sessionRaw = $redis->get("session:{$token}");
if (!$sessionRaw) {
    jsonResponse(['error' => 'Invalid session'], 401);
}

$session = json_decode($sessionRaw, true);
$email = strtolower(trim($session['email'] ?? ''));
if (!$email) {
    jsonResponse(['error' => 'Invalid session payload'], 401);
}

try {
    $profiles = mongoProfileCollection();
    $profile = $profiles->findOne(['email' => $email]);

    $fallbackProfile = [
        'name' => $session['full_name'] ?? '',
        'email' => $email,
        'role' => 'user',
    ];

    if (!$profile) {
        jsonResponse(['data' => ['profile' => $fallbackProfile]]);
    }

    $doc = $profile->getArrayCopy();
    jsonResponse([
        'data' => [
            'profile' => [
                'name' => $doc['name'] ?? ($session['full_name'] ?? ''),
                'email' => $doc['email'] ?? $email,
                'role' => $doc['role'] ?? 'user',
            ],
        ],
    ]);
} catch (Throwable $e) {
    jsonResponse(['error' => 'Failed to load profile'], 500);
}
