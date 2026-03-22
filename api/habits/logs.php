<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/mongo.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$session = requireAuthSession();
$days = (int) ($_GET['days'] ?? 14);
if ($days < 1 || $days > 60) {
    $days = 14;
}

$startDate = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));

try {
    $collection = mongoHabitLogsCollection();
    $cursor = $collection->find(
        [
            'user_id' => $session['user_id'],
            'date' => ['$gte' => $startDate],
            'habit' => ['$in' => ['gym', 'study', 'water']],
        ],
        [
            'sort' => ['date' => 1, 'habit' => 1],
            'projection' => ['_id' => 0, 'habit' => 1, 'date' => 1, 'value' => 1],
        ]
    );

    $logs = [];
    foreach ($cursor as $doc) {
        $logs[] = [
            'habit' => (string) ($doc['habit'] ?? ''),
            'date' => (string) ($doc['date'] ?? ''),
            'value' => (int) ($doc['value'] ?? 0),
        ];
    }

    jsonResponse(['data' => ['logs' => $logs, 'days' => $days]]);
} catch (Throwable $e) {
    jsonResponse(['data' => ['logs' => [], 'days' => $days, 'degraded' => true]]);
}
