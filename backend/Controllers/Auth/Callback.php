<?php

// Github redirects here after I authorize the OAuth app.
// Exchanges the code for an access token, verifies the user me,
// and then creates a session and redirects to the admin dashboard

require_once './backend/config/config.php';
session_start();

// Validate state to prevent CSRF
$state = $_GET['state'] ?? '';
$code = $_GET['code'] ?? '';
$sessionState = $_SESSION['oauth_state'] ?? '';

if (empty($code) || empty($state) || !hash_equals($sessionState, $state)) {
    header('Location: /auth/login.html?erro=oauth_failed');
}

unset($_SESSION['oauth_state']);

// Exchange code for access token
$tokenResponse = githubPost('https://github.com/login/oauth/access_token', [
    'client_id' => GITHUB_CLIENT_ID,
    'client_secret' => GITHUB_CLIENT_SECRET,
    'code' => $code,
    'redirect_uri' => OAUTH_CALLBACK_URL,
]);

if (empty($tokenResponse['access_token'])) {
    header('Location: /auth/login.html?error=oauth_failed');
    exit;
}

$accessToken = $tokenResponse['access_token'];

// Fetch Github user profile
$user = githubGet('https://api.github.com/user', $accessToken);

if (empty($user['login'])) {
    header('Location: /auth/login.html?error=oauth_failed');
    exit;
}

// Verify its me
if (strtolower($user['login']) !== strtolower(GITHUB_USERNAME)) {
    header('Location: /auth/login.html?error=unauthorized');
    exit;
}

//  Create session
session_regenerate_id(true);
$_SESSION['authenticated'] = true;
$_SESSION['github_user'] = [
    'login' => $user['login'],
    'name' => $user['name'] ?? $user['login'],
    'avatar_url' => $user['avatar_url'] ?? '',
];

header('Location: /admin/index.html');
exit;

// Helpers
function githubPost(string $url, array $data): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($data),
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true) ?? [];
}

function githubGet(string $url, string $token): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'User-Agent: portfolio-admin',
            'Accept: application/json',
        ],
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true) ?? [];
}
