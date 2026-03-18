<?php

namespace App\Controllers;

use App\Services\ProjectService;

require_once './backend/config/config.php';

class PublicProjectsController {
// Public endpoint - no authentication required.
// Frontend calls this to display projects to visitors.

private $projectService;

public function __construct(ProjectService $projectService) {
    $this->projectService = $projectService;
}

public function handleRequest(): void {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');    
    
    $projects = $this->projectService->getAllProjects();
    // Only return featured projects if ?featured=1 is passed
    if (isset($_GET['featured']) && $_GET['featured'] === '1') {
        $projects = array_values(
            array_filter($projects, fn($p) => !empty($p['featured']) )
        );
    }

    echo json_encode(['status' => 'success', 'projects' => $projects]);
    exit;
    
    }

}

