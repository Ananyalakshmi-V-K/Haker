<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$root = dirname(__DIR__);
if (file_exists($root . '/.env')) {
    $dotenv = Dotenv::createImmutable($root);
    $dotenv->safeLoad();
}
