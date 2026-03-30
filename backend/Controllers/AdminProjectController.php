<?php

namespace App\Controllers;

use App\Infrastructure\Database\ProjectRepository;

class AdminProjectController
{
    private $projectRepository;

    public function __construct(ProjectRepository $projectRepository)
    {
        $this->projectRepository = $projectRepository;
    }

    // GET /api/admin/projects
    public function listProjects(): void
    {
        header('Content-Type: application/json');

        $page     = max(1, (int) ($_GET['page']  ?? 1));
        $limit    = min(100, max(1, (int) ($_GET['limit'] ?? 10)));
        $search   = trim($_GET['search'] ?? '') ?: null;
        $featured = isset($_GET['featured'])
            ? filter_var($_GET['featured'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            : null;

        $result     = $this->projectRepository->getPaginatedProjects($page, $limit, $search, $featured);
        $totalPages = max(1, (int) ceil($result['total'] / $limit));

        echo json_encode([
            'status' => 'success',
            'data'   => [
                'projects'    => $result['projects'],
                'total'       => $result['total'],
                'page'        => $page,
                'limit'       => $limit,
                'total_pages' => $totalPages,
            ],
        ]);
        exit;
    }

    // GET /api/admin/projects/{id}
    public function getProject(int $id): void
    {
        header('Content-Type: application/json');

        $project = $this->projectRepository->getProjectById($id);
        if (!$project) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Project not found']);
            exit;
        }

        echo json_encode(['status' => 'success', 'data' => $project]);
        exit;
    }

    // POST /api/admin/projects
    public function createProject(): void
    {
        header('Content-Type: application/json');

        $body  = $this->parseJsonBody();
        $error = $this->validateProject($body);
        if ($error) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => $error]);
            exit;
        }

        $this->projectRepository->createProject($this->sanitize($body));

        http_response_code(201);
        echo json_encode(['status' => 'success', 'message' => 'Project created']);
        exit;
    }

    // PUT /api/admin/projects/{id}
    public function updateProject(int $id): void
    {
        header('Content-Type: application/json');

        $project = $this->projectRepository->getProjectById($id);
        if (!$project) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Project not found']);
            exit;
        }

        $body  = $this->parseJsonBody();
        $error = $this->validateProject($body);
        if ($error) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => $error]);
            exit;
        }

        $updated = $this->projectRepository->updateProject($id, $this->sanitize($body));
        if (!$updated) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to update project']);
            exit;
        }

        echo json_encode(['status' => 'success', 'message' => 'Project updated']);
        exit;
    }

    // DELETE /api/admin/projects/{id}
    public function deleteProject(int $id): void
    {
        header('Content-Type: application/json');

        $project = $this->projectRepository->getProjectById($id);
        if (!$project) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Project not found']);
            exit;
        }

        $deleted = $this->projectRepository->deleteProject($id);
        if (!$deleted) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete project']);
            exit;
        }

        echo json_encode(['status' => 'success', 'message' => 'Project deleted']);
        exit;
    }

    // GET /api/admin/projects/export
    public function exportCsv(): void
    {
        $projects = $this->projectRepository->getAllProjectsForExport();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="projects_' . date('Y-m-d') . '.csv"');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Title', 'Description', 'Tech Stack', 'GitHub URL', 'Live URL', 'Featured', 'Created At']);

        foreach ($projects as $p) {
            fputcsv($out, [
                $p['id'],
                $p['title'],
                $p['description'],
                $p['tech_stack'],
                $p['github_url'],
                $p['detail_url'],
                $p['featured'] ? 'Yes' : 'No',
                $p['created_at'],
            ]);
        }

        fclose($out);
        exit;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function validateProject(array $data): ?string
    {
        if (empty(trim($data['title']       ?? ''))) return 'Title is required';
        if (empty(trim($data['description'] ?? ''))) return 'Description is required';
        if (empty($data['tech_stack']))              return 'At least one technology is required';
        if (!is_array($data['tech_stack']))          return 'Tech stack must be an array';
        return null;
    }

    private function sanitize(array $data): array
    {
        return [
            'title'       => htmlspecialchars(trim($data['title']       ?? ''), ENT_QUOTES),
            'description' => htmlspecialchars(trim($data['description'] ?? ''), ENT_QUOTES),
            'tech_stack'  => array_map(
                fn($t) => htmlspecialchars(trim($t), ENT_QUOTES),
                (array) ($data['tech_stack'] ?? [])
            ),
            'github_url'  => filter_var($data['github_url']  ?? '', FILTER_SANITIZE_URL),
            'live_url'    => filter_var($data['live_url']     ?? '', FILTER_SANITIZE_URL),
            'detail_url'  => filter_var($data['detail_url']  ?? '', FILTER_SANITIZE_URL),
            'featured'    => (bool) ($data['featured'] ?? false),
        ];
    }

    private function parseJsonBody(): array
    {
        $data = json_decode(file_get_contents('php://input'), true);
        return is_array($data) ? $data : [];
    }
}