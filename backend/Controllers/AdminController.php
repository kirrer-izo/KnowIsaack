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
            // Stat card values
            'total_users'             => $this->userRepository->countAll(),
            'verified_users'          => $this->userRepository->countVerified(),
            'total_projects'          => $this->projectRepository->countAll(),
            'featured_projects'       => $this->projectRepository->countFeatured(),
            'failed_logins_24h'       => $this->loginActivityRepository->countFailedLast24Hours(),

            // Stat card deltas
            'new_users_7d'            => $this->userRepository->countNewSinceDays(7),
            'new_verified_today'      => $this->userRepository->countVerifiedToday(),
            'failed_logins_yesterday' => $this->loginActivityRepository->countFailedYesterday(),

            // Login activity bar chart — last 7 days
            'logins_7d'               => $this->loginActivityRepository->getLast7DaysSummary(),

            // Recent projects table — last 5
            'recent_projects'         => $this->projectRepository->getRecent(5),
        ];

        echo json_encode([
            'status' => 'success',
            'data'   => $stats,
        ]);
        exit;
    }
}