<?php

namespace App\Controllers;

use App\Infrastructure\Database\ProjectRepository;

// Public endpoint — no authentication required.
// Reads directly from PostgreSQL via ProjectRepository.

class PublicProjectsController
{
    private ProjectRepository $projectRepository;

    public function __construct(ProjectRepository $projectRepository)
    {
        $this->projectRepository = $projectRepository;
    }

    public function handleRequest(): void
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');

        $projects = $this->projectRepository->getAllProjects();

        // Filter to featured only when ?featured=1 is passed
        if (isset($_GET['featured']) && $_GET['featured'] === '1') {
            $projects = array_values(
                array_filter($projects, fn($p) => !empty($p['featured']))
            );
        }

        echo json_encode(['status' => 'success', 'projects' => $projects]);
        exit;
    }
}