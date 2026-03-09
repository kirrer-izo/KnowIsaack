<?php

namespace App\Controllers;

use App\Services\ProjectService;

require_once './backend/config/config.php';


class ProjectsController {

private $projectService;

public function __construct(ProjectService $projectService)
{
    $this->projectService = $projectService;
}

// Handles all project CRUD operations via the GitHub API.
// GET /api/projects.php -> returns all projects as JSON
// POST /api/projects.php ->creates or updates a project
// DELETE /api/projects -> removes a project by id
public function handleRequest(): void {
    require_once './backend/config/guard_user.php';
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
    $projects = $this->projectService->getAllProjects();
    $this->jsonSuccess(['projects' => $projects]);
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

    // Update existing or add new
    $id = $body['id'] ?? null;

    if ($id) {
        // Update
        $this->projectService->updateProject($id, $this->sanitizeProject($body));
        $this->jsonSuccess(['message' => 'Project updated']);
    } else {
        // Create
        $this->projectService->createProject($this->sanitizeProject($body));
        $this->jsonSuccess(['message' => 'Project created']);
    }

}

//  -- DELETE -- remove a project

private function handleDelete(): void {
    $id = $_GET['id'] ?? '';

    if (empty($id)) {
        $this->jsonError(400, 'Project ID is required');
        return;
    }

    $this->projectService->deleteProject((int)$id);
    $this->jsonSuccess(['message' => 'Project deleted']);
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

    if (!empty($_SESSION['github_user'])) {
        echo json_encode([
            'authenticated' => true,
            'user' => $_SESSION['github_user'],
            'is_admin' => true
        ]);
        return;
    }
    
    echo json_encode([
        'authenticated' => true,
        'user' => $_SESSION['db_user'],
        'is_admin' => false
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

}

