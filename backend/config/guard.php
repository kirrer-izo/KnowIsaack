<?php

// Include this at the top of any php file that requires authentication
// Redirects unauthenticated requests to the login page

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['authenticated'])) {
    http_response_code(401);
    header('Location: /auth/login.html');
    exit;
}