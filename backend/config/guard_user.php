<?php

// Check that either github_user OR db_user exists

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['github_user']) && empty($_SESSION['db_user'])) {
    http_response_code(401);
    header('Location: /auth/login.html');
    exit;
}