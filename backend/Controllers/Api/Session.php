<?php

// Called by admin HTML pages to check if the user is authenticated.
// Returns user info if session is valid, 401 if not.

require_once './backend/config/config.php';

session_start();
header('Conctent-Type: application/json');

if (empty($_SESSION['authenticated'])) {
    http_response_code(401);
    echo json_encode(['authencticaed' => false]);
    exit;
}

echo json_encode([
    'authenticated' => true,
    'user' => $_SESSION['github_user'],
]);