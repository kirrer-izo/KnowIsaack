<?php

require_once __DIR__ . '/../../config/config.php';

$dsn = "pgsql:host=" . DB_HOST . ";dbname=" . DB_NAME;
$pdo = new PDO($dsn, DB_USER, DB_PASSWORD);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create migrations tracking table if it doesn't exist
$pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
    id SERIAL PRIMARY KEY,
    migration VARCHAR(255) NOT NULL,
    run_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$migrationsPath = __DIR__ . '/migrations';
$files = glob($migrationsPath . '/*.sql');
sort($files);

foreach ($files as $file) {
    $name = basename($file);

    // Check if already run
    $stmt = $pdo->prepare("SELECT id FROM migrations WHERE migration = ?");
    $stmt->execute([$name]);

    if ($stmt->fetch()) {
        echo "Skipping: $name (already run)\n";
        continue;
    }

    // Run the migration
    $sql = file_get_contents($file);
    $pdo->exec($sql);

    // Record it
    $stmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
    $stmt->execute([$name]);

    echo "Migrated: $name\n";
}

echo "Done.\n";