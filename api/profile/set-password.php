<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/auth.php';
require_once __DIR__ . '/../utils/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$session = requireAuthSession();
$body = readJsonInput();
$password = (string) ($body['password'] ?? '');

if (strlen($password) < 6) {
    jsonResponse(['error' => 'Password must be at least 6 characters'], 422);
}

if (strlen($password) > 72) {
    jsonResponse(['error' => 'Password is too long'], 422);
}

try {
    $pdo = mysqlConnection();

    $stmt = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id AND email = :email LIMIT 1');
    $stmt->bindValue(':password_hash', password_hash($password, PASSWORD_DEFAULT), PDO::PARAM_STR);
    $stmt->bindValue(':id', $session['user_id'], PDO::PARAM_INT);
    $stmt->bindValue(':email', $session['email'], PDO::PARAM_STR);
    $stmt->execute();

    if ($stmt->rowCount() < 1) {
        jsonResponse(['error' => 'Unable to update password'], 404);
    }

    jsonResponse(['data' => ['updated' => true]]);
} catch (Throwable $e) {
    jsonResponse(['error' => 'Failed to update password'], 500);
}
