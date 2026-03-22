<?php

declare(strict_types=1);

/**
 * Heroku Post-Deployment Setup Script
 * This runs after `heroku run` to initialize databases
 */

require_once __DIR__ . '/../api/bootstrap.php';

echo "=== GUVI App Heroku Setup ===\n\n";

// Create MySQL tables
try {
    echo "Setting up MySQL database...\n";
    $pdo = mysqlConnection();
    
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    
    // Split by semicolon to execute multiple statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
            echo "  ✓ Executed: " . substr($statement, 0, 50) . "...\n";
        }
    }
    
    echo "✓ MySQL database ready\n\n";
} catch (Exception $e) {
    echo "✗ MySQL error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test MongoDB connection
try {
    echo "Testing MongoDB connection...\n";
    $mongoLogs = mongoHabitLogsCollection();
    $mongoProfiles = mongoProfileCollection();
    
    // Create collections if they don't exist
    $mongoLogs->insertOne([
        'user_id' => 0,
        'habit' => 'test',
        'date' => date('Y-m-d'),
        'value' => 0,
        'created_at' => time(),
        'updated_at' => time()
    ]);
    
    $mongoLogs->deleteOne(['user_id' => 0]);
    
    echo "✓ MongoDB connection verified\n\n";
} catch (Exception $e) {
    echo "⚠ MongoDB warning: " . $e->getMessage() . "\n";
    echo "  Make sure to set MONGO_URI in Heroku config\n\n";
}

// Test Redis connection
try {
    echo "Testing Redis connection...\n";
    $redis = redisClient();
    $redis->ping();
    echo "✓ Redis connection verified\n\n";
} catch (Exception $e) {
    echo "⚠ Redis warning: " . $e->getMessage() . "\n\n";
}

echo "=== Setup Complete ===\n";
echo "\nYour app is ready! 🎉\n";
echo "Visit: " . ($_ENV['APP_URL'] ?? 'https://your-app.herokuapp.com') . "\n";
echo "\nTest Credentials:\n";
echo "  Email: testuser@example.com\n";
echo "  Password: Pass@123\n";
