<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/mongo.php';
require_once __DIR__ . '/../utils/auth.php';
require_once __DIR__ . '/../utils/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$session = requireAuthSession();
$body = readJsonInput();

$age = isset($body['age']) ? (int) $body['age'] : null;
$dob = isset($body['dob']) ? trim((string) $body['dob']) : '';
$contact = isset($body['contact']) ? trim((string) $body['contact']) : '';

try {
    $email = strtolower(trim($session['email']));

    $updateData = [
        'name' => $session['full_name'],
        'email' => $email,
        'role' => 'user',
        'user_id' => $session['user_id'],
        'updated_at' => time(),
    ];

    if ($age !== null && $age >= 0 && $age <= 150) {
        $updateData['age'] = $age;
    }

    if ($dob) {
        $updateData['dob'] = $dob;
    }

    if ($contact) {
        $updateData['contact'] = $contact;
    }

    $profiles = mongoProfileCollection();
    $result = $profiles->updateOne(
        ['email' => $email],
        [
            '$set' => $updateData,
            '$setOnInsert' => [
                'created_at' => time(),
            ],
        ],
        ['upsert' => true]
    );

    jsonResponse([
        'data' => [
            'updated' => ($result->getModifiedCount() > 0 || $result->getUpsertedId() !== null),
            'profile' => $updateData,
        ],
    ]);
} catch (Throwable $e) {
    jsonResponse(['error' => 'Failed to update profile'], 500);
}
