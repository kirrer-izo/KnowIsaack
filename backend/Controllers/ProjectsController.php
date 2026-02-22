<?php

namespace App\Controllers;

require_once './backend/config/config.php';


class ProjectsController {
// Handles all project CRUD operations via the GitHub API.
// GET /api/projects.php -> returns all projects as JSON
// POST /api/projects.php ->creates or updates a project
// DELETE /api/projects -> removes a project by id

public function handleRequest(): void {
    require_once './backend/config/guard.php';
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    match ($method) {
        'GET' => $this->handleGet(),
        'POST' => $this->handlePost(),
        'DELETE' => $this->handleDelete(),
        default => $this->jsonError(405, 'Method not allowed'),  
    };
}

// -- GET -- fetch projects from GitHub

private function handleGet(): void {
    $result = $this->githubGetFile();

    if (!$result['success']) {
        // File doesnt exist yet - return empty array
        $this->jsonSuccess(['projects' => []]);
        return;
    }

    $data = json_decode(base64_decode($result['content']), true) ?? ['projects' => []];
    $this->jsonSuccess($data);
}

// -- POST -- create or update a project

private function handlePost(): void {
    $body = json_decode(file_get_contents('php://input'), true);

    if (empty($body)) {
        $this->jsonError(400, 'Request body is required');
        return;
    }

    // Validate required fields
    $required = ['title', 'description', 'tech_stack'];
    foreach ($required as $field) {
        if (empty($body[$field])) {
            $this->jsonError(400, "Field '{$field}' is required");
            return;
        }
    }

    // Fetch current data
    $result = $this->githubGetFile();
    $sha = $result['sha'] ?? null;
    $data = $result['success']
    ? json_decode(base64_decode($result['content']), true)
    : ['projects' => []];
    $projects = $data['projects'] ?? [];

    // Update existing or add new
    $id = $body['id'] ?? null;

    if ($id) {
        // Update
        $found = false;
        foreach ($projects as &$project) {
            if ($project['id'] === $id) {
                $project = array_merge($project, $this->sanitizeProject($body));
                $project['updated_at'] = date('c');
                $found = true;
                break;
            }
        }

        unset($project);

        if(!$found) {
            $this->jsonError(404, 'Project not found');
            return;
        }
    } else {
        // Create
        $newProject = array_merge($this->sanitizeProject($body), [
            'id' => uniqid('proj_', true),
            'created_at' => date('c'),
            'updated_at' => date('c'),
        ]);

        $projects[] = $newProject;
    }

    $data['projects'] = $projects;
    $saved = $this->githubSaveFile($data, $sha, $id ? 'Update project' : 'Add project');

    if (!$saved) {
        $this->jsonError(500, 'Failed to save to GitHub');
        return;
    }

    $this->jsonSuccess(['message' => $id ? 'Project updated' : 'Project created', 'projects' => $projects]);
}

//  -- DELETE -- remove a project

private function handleDelete(): void {
    $id = $_GET['id'] ?? '';

    if (empty($id)) {
        $this->jsonError(400, 'Project ID is required');
        return;
    }

    $result = $this->githubGetFile();
    $sha = $result['sha'] ?? null;
    $data = $result['success']
    ? json_decode(base64_decode($result['content']), true)
    : ['projects' => []];

    $projects = $data['projects'] ?? [];
    $filtered = array_values(array_filter($projects, fn($p) => $p['id'] !== $id));

    if (count($filtered) === count($projects)) {
        $this->jsonError(404, 'Project not found');
        return;
    }

    $data['projects'] = $filtered;
    $saved = $this->githubSaveFile($data, $sha, 'Delete project');

    if (!$saved) {
        $this->jsonError(500, 'Failed to save to GitHub');
        return;
    }

    $this->jsonSuccess(['message' => 'Project deleted', 'projects' => $filtered]);
}


// Called by admin HTML pages to check if the user is authenticated.
// Returns user info if session is valid, 401 if not.

public function session(): void {
    header('Content-Type: application/json');
    
    if (empty($_SESSION['authenticated'])) {
        http_response_code(401);
        echo json_encode(['authenticated' => false]);
        exit;
    }
    
    echo json_encode([
        'authenticated' => true,
        'user' => $_SESSION['github_user'],
    ]);
}


// Data Helpers

private function sanitizeProject(array $data) {
    return [
        'title' => htmlspecialchars(trim($data['title'] ?? ''), ENT_QUOTES),
        'description' => htmlspecialchars(trim($data['description'] ?? ''), ENT_QUOTES),
        'tech_stack' => array_map(
            fn($t) => htmlspecialchars(trim($t), ENT_QUOTES),
            (array) ($data['tech_stack'] ?? [])
        ),
        'github_url'  => filter_var($data['github_url']  ?? '', FILTER_SANITIZE_URL),
        'live_url'    => filter_var($data['live_url']    ?? '', FILTER_SANITIZE_URL),
        'detail_url'  => filter_var($data['detail_url'] ?? '', FILTER_SANITIZE_URL),
        'featured'    => (bool) ($data['featured'] ?? false),

    ];
}

private function jsonSuccess(array $data): void {
    http_response_code(200);
    echo json_encode(['status' => 'success', ...$data]);
    exit;
}

private function jsonError(int $code, string $message): void {
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

// GitHub API Helpers

private function githubGetFile(): array {
    $url = 'https://api.github.com/repos/' . GITHUB_REPO . '/contents/' . GITHUB_FILE_PATH;

    $ch  = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . GITHUB_PERSONAL_ACCESS_TOKEN,
            'User-Agent: portfolio-admin',
            'Accept: application/vnd.github+json',
        ],
    ]);

    $response = curl_exec($ch);
    $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status !== 200) {
        return ['success' => false];
    }

    $data = json_decode($response, true);
    return [
        'success' => true,
        'content' => $data['content'],
        'sha'     => $data['sha'],
    ];
}

private function githubSaveFile(array $data, ?string $sha, string $message): bool {
    $url     = 'https://api.github.com/repos/' . GITHUB_REPO . '/contents/' . GITHUB_FILE_PATH;
    $payload = [
        'message' => $message,
        'content' => base64_encode(json_encode($data, JSON_PRETTY_PRINT)),
    ];

    if ($sha) {
        $payload['sha'] = $sha;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'PUT',
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . GITHUB_PERSONAL_ACCESS_TOKEN,
            'User-Agent: portfolio-admin',
            'Content-Type: application/json',
            'Accept: application/vnd.github+json',
        ],
    ]);

    $response = curl_exec($ch);
    $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return in_array($status, [200, 201]);
}

}

