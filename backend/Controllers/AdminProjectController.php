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
        $page = (int) ($_GET['page'] ?? 1);
        $limit = (int) ($_GET['limit'] ?? 10);
        $search = $_GET['search'] ?? null;
        $featured = isset($_GET['featured']) ? filter_var($_GET['featured'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;

        $result = $this->projectRepository->getPaginatedProjects($page, $limit, $search, $featured);

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'data' => [
                'projects' => $result['projects'],
                'total' => $result['total'],
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($result['total'] / $limit),
            ]
        ]);
        exit;
    }

    // DELETE /api/admin/projects/{id}
    public function deleteProject(int $id): void
    {
        $deleted = $this->projectRepository->deleteProject($id);
        if ($deleted) {
            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => 'Project deleted']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete project']);
        }
        exit;
    }

    // GET /api/admin/projects/export
    public function exportCsv(): void
    {
        $projects = $this->projectRepository->getAllProjectsForExport();

        $filename = 'projects_export_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        // Headers
        fputcsv($output, ['ID', 'Title', 'Description', 'Tech Stack', 'GitHub URL', 'Live URL', 'Featured', 'Created At']);

        foreach ($projects as $project) {
            fputcsv($output, [
                $project['id'],
                $project['title'],
                $project['description'],
                $project['tech_stack'],
                $project['github_url'],
                $project['detail_url'],
                $project['featured'] ? 'Yes' : 'No',
                $project['created_at']
            ]);
        }
        fclose($output);
        exit;
    }
}