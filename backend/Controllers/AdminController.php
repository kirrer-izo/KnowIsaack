<?php

namespace App\Controllers;

use App\Infrastructure\Database\LoginActivityRepository;
use App\Infrastructure\Database\ProjectRepository;
use App\Infrastructure\Database\UserRepository;

// Controller for admin dashboard API endpoints

class AdminController
{
    private $userRepository;
    private $projectRepository;
    private $loginActivityRepository;

    public function __construct(UserRepository $userRepository, ProjectRepository $projectRepository, LoginActivityRepository $loginActivityRepository)
    {
        $this->userRepository = $userRepository;
        $this->projectRepository = $projectRepository;
        $this->loginActivityRepository = $loginActivityRepository;
    }

    // Return stats for the admin dashboard: user counts, project counts, failed logins
    // GET /api/admin/stats

    public function stats(): void
    {
        header('Content-Type: application/json');

        $stats = [
            'total_users' => $this->userRepository->countAll(),
            'verified_users' => $this->userRepository->countVerified(),
            'total_projects' => $this->projectRepository->countAll(),
            'featured_projects' => $this->projectRepository->countFeatured(),
            'failed_logins_24h' => $this->loginActivityRepository->countFailedLast24Hours(),
        ];

        echo json_encode([
            'status' => 'success',
            'data' => $stats
        ]);
        exit;
    }
}