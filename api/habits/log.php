<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/mongo.php';
require_once __DIR__ . '/../config/redis.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$session = requireAuthSession();
$body = readJsonInput();

$habit = strtolower(trim((string) ($body['habit'] ?? '')));
$date = trim((string) ($body['date'] ?? ''));
$value = (int) ($body['value'] ?? 0);

$allowedHabits = ['gym', 'study', 'water'];
if (!in_array($habit, $allowedHabits, true)) {
    jsonResponse(['error' => 'Invalid habit type'], 422);
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    jsonResponse(['error' => 'Date must be in YYYY-MM-DD format'], 422);
}

if ($value < 0 || $value > 1000) {
    jsonResponse(['error' => 'Value must be between 0 and 1000'], 422);
}

try {
    $collection = mongoHabitLogsCollection();

    $collection->updateOne(
        [
            'user_id' => $session['user_id'],
            'habit' => $habit,
            'date' => $date,
        ],
        [
            '$set' => [
                'value' => $value,
                'updated_at' => time(),
            ],
            '$setOnInsert' => [
                'created_at' => time(),
            ],
        ],
        ['upsert' => true]
    );

    $redis = redisClient();
    $redis->del(["streak-summary:{$session['user_id']}"]);

    jsonResponse(['data' => ['saved' => true]]);
} catch (Throwable $e) {
    jsonResponse(['error' => 'Unable to save habit log'], 500);
}
