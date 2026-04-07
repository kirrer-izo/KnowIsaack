<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\Integration\IntegrationTestCase;
use App\Infrastructure\Database\DatabaseConnection;

abstract class FeatureTestCase extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        if (!defined('PHPUNIT_RUNNING')) {
            define('PHPUNIT_RUNNING', true);
        }

        // Set the testing flag for the router
        $GLOBALS['IS_TESTING'] = true;

        DatabaseConnection::getInstance()->setExternalConnection($this->pdo);

        $_SESSION = [];
        $_POST = [];
        $_GET = [];
        $_COOKIE = [];
    }

    protected function postJson(string $uri, array $data = []): array
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = $uri;
        $_POST = $data;

        ob_start();
        try {
            require __DIR__ . '/../../backend/routes.php';
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        $content = ob_get_clean();

        return [
            'status' => http_response_code(),
            'data'   => json_decode($content, true) ?: $content
        ];
    }
}