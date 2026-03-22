<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

function mysqlConnection(): PDO
{
    $host = $_ENV['MYSQL_HOST'] ?? '127.0.0.1';
    $port = $_ENV['MYSQL_PORT'] ?? '3306';
    $db = $_ENV['MYSQL_DB'] ?? '';
    $user = $_ENV['MYSQL_USER'] ?? '';
    $password = $_ENV['MYSQL_PASSWORD'] ?? '';

    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

    return new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
