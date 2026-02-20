<?php

// Redirects the user to Github's OAuth authorization page.

require_once './backend/config/config.php';
session_start();

// Generate a random state token to prevent CRSF attacks
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

$params = http_build_query([
    'client_id' => GITHUB_CLIENT_ID,
    'redirect_uri' => OAUTH_CALLBACK_URL,
    'scope' => 'read:user',
    'state' => $state
]);

header('Location: https://github.com/login/oauth/authorize?' .$params);
exit;