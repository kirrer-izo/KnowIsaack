<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use PDO;

abstract class IntegrationTestCase extends TestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        // Use environment variables from phpunit.xml
        $dsn = sprintf(
            "pgsql:host=%s;port=%s;dbname=%s",
            getenv('DB_HOST') ?: 'localhost',
            getenv('DB_PORT') ?: '5432',
            getenv('DB_DATABASE_TEST') ?: 'knowisaack_test'
        );

        $this->pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASS'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Start a transaction to keep the DB clean
        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        // Roll back all changes made during the test
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }
}