<?php

// Public endpoint - no authentication required.
// Frontend calls this to display projects to visitors.

require_once './backend/config/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$url = 'https://api.github.com/repos/' . GITHUB_REPO . '/contents/' . GITHUB_FILE_PATH;
$ch  = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . GITHUB_PAT,
        'User-Agent: portfolio-admin',
        'Accept: application/vnd.github+json',
    ],
]);

$response = curl_exec($ch);
$status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($status !== 200) {
    // Return empty array gracefully so portfolio never breaks
    echo json_encode(['status' => 'success', 'projects' => []]);
    exit;
}

$file = json_decode($response, true);
$data = json_decode(base64_decode($file['content']), true) ?? ['projects' => []];

// Only return featured projects if ?featured=1 is passed
if (isset($_GET['featured']) && $_GET['featured'] === '1') {
    $data['projects'] = array_values(
        array_filter($data['projects'], fn($p) => !empty($p['featured']))
    );
}

echo json_encode(['status' => 'success', ...$data]);