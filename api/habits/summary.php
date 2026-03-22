<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/mongo.php';
require_once __DIR__ . '/../config/redis.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth.php';

function calculateStreak(array $dateSet): int
{
    $streak = 0;
    $cursorDate = date('Y-m-d');

    while (isset($dateSet[$cursorDate]) && $dateSet[$cursorDate] === true) {
        $streak++;
        $cursorDate = date('Y-m-d', strtotime($cursorDate . ' -1 day'));
    }

    return $streak;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$session = requireAuthSession();
$redis = redisClient();
$cacheKey = "streak-summary:{$session['user_id']}";

$cached = $redis->get($cacheKey);
if ($cached) {
    $decoded = json_decode($cached, true);
    if (is_array($decoded)) {
        jsonResponse(['data' => $decoded]);
    }
}

try {
    $collection = mongoHabitLogsCollection();
    $cursor = $collection->find(
        [
            'user_id' => $session['user_id'],
            'habit' => ['$in' => ['gym', 'study', 'water']],
            'value' => ['$gt' => 0],
        ],
        [
            'projection' => ['_id' => 0, 'habit' => 1, 'date' => 1],
        ]
    );

    $habitDates = [
        'gym' => [],
        'study' => [],
        'water' => [],
    ];

    foreach ($cursor as $doc) {
        $habit = (string) ($doc['habit'] ?? '');
        $date = (string) ($doc['date'] ?? '');

        if (isset($habitDates[$habit]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $habitDates[$habit][$date] = true;
        }
    }

    $summary = [
        'streaks' => [
            'gym' => calculateStreak($habitDates['gym']),
            'study' => calculateStreak($habitDates['study']),
            'water' => calculateStreak($habitDates['water']),
        ],
    ];

    $ttl = (int) ($_ENV['REDIS_STREAK_CACHE_TTL'] ?? 300);
    $redis->setex($cacheKey, $ttl, json_encode($summary));

    jsonResponse(['data' => $summary]);
} catch (Throwable $e) {
    jsonResponse([
        'data' => [
            'streaks' => [
                'gym' => 0,
                'study' => 0,
                'water' => 0,
            ],
            'degraded' => true,
        ],
    ]);
}
