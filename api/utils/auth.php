<?php

declare(strict_types=1);

require_once __DIR__ . '/response.php';
require_once __DIR__ . '/../config/redis.php';

function requireAuthSession(): array
{
    $token = bearerToken();
    if (!$token) {
        jsonResponse(['error' => 'Missing token'], 401);
    }

    $redis = redisClient();
    $sessionRaw = $redis->get("session:{$token}");

    if (!$sessionRaw) {
        jsonResponse(['error' => 'Session expired or invalid'], 401);
    }

    $session = json_decode($sessionRaw, true);
    if (!is_array($session) || !isset($session['user_id'], $session['email'])) {
        jsonResponse(['error' => 'Invalid session payload'], 401);
    }

    return [
        'token' => $token,
        'user_id' => (int) $session['user_id'],
        'email' => (string) $session['email'],
        'full_name' => (string) ($session['full_name'] ?? ''),
    ];
}
