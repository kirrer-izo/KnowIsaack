<?php

use App\Controllers\Auth\GitHub\AuthController;
use App\Controllers\Auth\User\UserController;
use App\Controllers\ContactController;
use App\Infrastructure\Mail\ResendMailer;
use App\Controllers\ProjectsController;
use App\Controllers\PublicProjectsController;
use App\Infrastructure\Database\DatabaseConnection;
use App\Infrastructure\Database\EmailVerificationRepository;
use App\Infrastructure\Database\ProjectRepository;
use App\Infrastructure\Database\UserRepository;
use App\Services\ProjectService;
use App\Services\UserService;

session_start();

$request = $_SERVER['REQUEST_URI'];

// Remove subdirectory prefix from path if running in a subdirectory
$base_path = '/portfolio';
$path = str_replace($base_path, '', $request);

// Strip query string e.g. ?code=xxx&state=yyy — we only need the path
$path = strtok($path, '?');

// Routes that require a database connection
// Only these routes trigger PDO instantiation and dependency wiring
$db_routes = [
    '/api/projects', 
    '/api/public-projects', 
    '/api/session',
    '/auth/login',
    '/auth/register',
    '/auth/verify'
];

// Wire up database dependencies only when needed
if (in_array($path, $db_routes)) {
    $pdo = DatabaseConnection::getInstance()->getConnection();
    $projectRepository = new ProjectRepository($pdo);
    $projectService = new ProjectService($projectRepository);
    $userRepository = new UserRepository($pdo);
    $emailVerificationRepository = new EmailVerificationRepository($pdo);
    $mailer = new ResendMailer();
    $userService = new UserService($userRepository, $emailVerificationRepository, $mailer);
    $userController = new UserController($userService);
}

switch ($path) {
    // Public pages
    case '/':
    case '/home':
        require __DIR__ . '/../frontend/pages/home.html';
        break;
    case '/live-your-books':
        require __DIR__ . '/../frontend/pages/live-your-books.html';
        break;
    case '/sole-proprietor-crm':
        require __DIR__ . '/../frontend/pages/sole-proprietor-crm.html';
        break;
    
    // Auth Pages
    case '/auth/login':
        $userController->handleLoginRequest();
        break;
    case '/auth/register':
        $userController->handleRegisterRequest();
        break;
    case '/auth/logout':
        // Destroy session and redirect — handles both GitHub and MySQL sessions
        session_unset();
        session_destroy();
        header('Location: /auth/login?message=logged_out');
        exit;
    case '/auth/verify':
        // Handles email verification link clicked from inbox
        $userController->handleVerifyRequest();
        break;

    // Admin Pages — protected by guard inside each controller
    case '/admin':
        require __DIR__. '/../frontend/pages/admin/index.html';
        break;
    case '/admin/edit':
        require __DIR__ . '/../frontend/pages/admin/edit.html';
        break;

    // API
    case '/api/contact':
        // Contact form submission — uses ResendMailer, no DB needed
        $mailer = new ResendMailer();
        $controller = new ContactController($mailer);
        $controller->handleRequest();
        break;
    case '/api/session':
        // Returns current session state for frontend auth checks
        $controller = new ProjectsController($projectService);
        $controller->session();
        break;
    case '/api/projects':
        // Project CRUD — requires authentication via guard_user.php
        $controller = new ProjectsController($projectService);
        $controller->handleRequest();
        break;
    case '/api/public-projects':
        // Public project feed — no authentication required
        $controller = new PublicProjectsController($projectService);
        $controller->handleRequest();
        break;

    // GitHub OAuth flow
    case '/auth/authorize':
        $controller = new AuthController();
        $controller->authorize();
        break;
    case '/auth/callback':
        $controller = new AuthController();
        $controller->callback();
        break;

    // Health check endpoint
    case '/ping':
        http_response_code(200);
        echo 'PONG'; 
        exit;

    default: 
        http_response_code(404);
        echo "Page not found.";
        break;
}