<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mongo.php';
require_once __DIR__ . '/../utils/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$body = readJsonInput();
$fullName = trim((string) ($body['full_name'] ?? ''));
$email = strtolower(trim((string) ($body['email'] ?? '')));
$password = (string) ($body['password'] ?? '');

if (!$fullName || !$email || !$password) {
    jsonResponse(['error' => 'Name, email and password are required'], 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['error' => 'Please enter a valid email'], 422);
}

if (strlen($password) < 6) {
    jsonResponse(['error' => 'Password must be at least 6 characters'], 422);
}

try {
    $pdo = mysqlConnection();

    $checkStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $checkStmt->bindValue(':email', $email, PDO::PARAM_STR);
    $checkStmt->execute();

    if ($checkStmt->fetch()) {
        jsonResponse(['error' => 'Email is already registered'], 409);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $insertStmt = $pdo->prepare('INSERT INTO users (email, password_hash, full_name) VALUES (:email, :password_hash, :full_name)');
    $insertStmt->bindValue(':email', $email, PDO::PARAM_STR);
    $insertStmt->bindValue(':password_hash', $hash, PDO::PARAM_STR);
    $insertStmt->bindValue(':full_name', $fullName, PDO::PARAM_STR);
    $insertStmt->execute();

    $userId = (int) $pdo->lastInsertId();

    try {
        $profiles = mongoProfileCollection();
        $profiles->updateOne(
            ['email' => $email],
            [
                '$set' => [
                    'name' => $fullName,
                    'email' => $email,
                    'role' => 'user',
                    'user_id' => $userId,
                    'updated_at' => time(),
                ],
                '$setOnInsert' => [
                    'created_at' => time(),
                ],
            ],
            ['upsert' => true]
        );
    } catch (Throwable $e) {
    }

    jsonResponse(['data' => ['registered' => true]], 201);
} catch (Throwable $e) {
    jsonResponse(['error' => 'Unable to register user'], 500);
}
