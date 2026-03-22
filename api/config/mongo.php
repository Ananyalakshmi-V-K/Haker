<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use MongoDB\Client;
use MongoDB\Collection;

function mongoProfileCollection(): Collection
{
    $uri = $_ENV['MONGO_URI'] ?? 'mongodb://127.0.0.1:27017';
    $dbName = $_ENV['MONGO_DB'] ?? 'guvi_app';
    $collectionName = $_ENV['MONGO_PROFILE_COLLECTION'] ?? 'user_profiles';

    $client = new Client($uri);

    return $client->selectCollection($dbName, $collectionName);
}

function mongoHabitLogsCollection(): Collection
{
    $uri = $_ENV['MONGO_URI'] ?? 'mongodb://127.0.0.1:27017';
    $dbName = $_ENV['MONGO_DB'] ?? 'guvi_app';
    $collectionName = $_ENV['MONGO_HABIT_LOG_COLLECTION'] ?? 'habit_logs';

    $client = new Client($uri);

    return $client->selectCollection($dbName, $collectionName);
}
