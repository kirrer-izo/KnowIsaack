<?php

namespace App\Controllers;

abstract class BaseController {
    protected function jsonResponse(array $data, int $code = 200): void {
        header('Content-Type: application/json');
        http_response_code($code);
        echo json_encode($data);
        
        if (PHP_SAPI !== 'cli') {
            exit;
        }
    }

    protected function redirect(string $url, int $code = 302): void {
        if (PHP_SAPI !== 'cli') {
            header("Location: $url", true, $code);
            exit;
        }
        http_response_code($code);
    }
}