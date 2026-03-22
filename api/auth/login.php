<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/redis.php';
require_once __DIR__ . '/../utils/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$body = readJsonInput();
$email = strtolower(trim($body['email'] ?? ''));
$password = (string) ($body['password'] ?? '');

if (!$email || !$password) {
    jsonResponse(['error' => 'Email and password are required'], 422);
}

try {
    $pdo = mysqlConnection();

    $stmt = $pdo->prepare('SELECT id, email, password_hash, full_name FROM users WHERE email = :email LIMIT 1');
    $stmt->bindValue(':email', $email, PDO::PARAM_STR);
    $stmt->execute();

    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        jsonResponse(['error' => 'Invalid credentials'], 401);
    }

    $token = bin2hex(random_bytes(32));
    $ttl = (int) ($_ENV['SESSION_TTL_SECONDS'] ?? 3600);
    $expiresAt = (int) (microtime(true) * 1000) + ($ttl * 1000);

    $sessionData = json_encode([
        'user_id' => (int) $user['id'],
        'email' => $user['email'],
        'full_name' => $user['full_name'],
        'issued_at' => time(),
        'expires_in' => $ttl,
    ]);

    $redis = redisClient();
    $redis->setex("session:{$token}", $ttl, $sessionData);

    jsonResponse([
        'data' => [
            'token' => $token,
            'expires_at' => $expiresAt,
            'user' => [
                'id' => (int) $user['id'],
                'email' => $user['email'],
                'full_name' => $user['full_name'],
            ],
        ],
    ]);
} catch (Throwable $e) {
    jsonResponse(['error' => 'Server error during login'], 500);
}
