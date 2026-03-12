<?php

use App\Controllers\Auth\GitHub\AuthController;
use App\Controllers\Auth\User\UserController;
use App\Controllers\ContactController;
use App\Infrastructure\Mail\ResendMailer;
use App\Controllers\ProjectsController;
use App\Controllers\PublicProjectsController;
use App\Infrastructure\Database\MySQLConnection;
use App\Infrastructure\Database\ProjectRepository;
use App\Infrastructure\Database\UserRepository;
use App\Services\ProjectService;
use App\Services\UserService;

session_start();

$request = $_SERVER['REQUEST_URI'];

//  Remove subdirectory from path if you are using one
$base_path = '/portfolio';
$path = str_replace($base_path, '', $request);

// Strips ?code=xxx&state=yyy
$path = strtok($path, '?');

$db_routes = [
    '/api/projects', 
    '/api/public-projects', 
    '/api/session',
    '/auth/login',
    '/auth/register'
];

if (in_array($path, $db_routes)) {
    $pdo = MySQLConnection::getInstance()->getConnection();
    $projectRepository = new ProjectRepository($pdo);
    $projectService = new ProjectService($projectRepository);
}


switch ($path) {
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
        $userRepository = new UserRepository($pdo);
        $userService = new UserService($userRepository);
        $controller = new UserController($userService);
        $controller->handleLoginRequest();
        break;
    case '/auth/register':
        $userRepository = new UserRepository($pdo);
        $userService = new UserService($userRepository);
        $controller = new UserController($userService);
        $controller->handleRegisterRequest();
        break;
    case '/auth/logout':
        // Simple logout - no controller needed
        session_unset();
        session_destroy();
        header('Location: /auth/login?message=logged_out');
        exit;

    // Admin Pages
    case '/admin':
        require __DIR__. '/../frontend/pages/admin/index.html';
        break;
    case '/admin/edit':
        require __DIR__ . '/../frontend/pages/admin/edit.html';
        break;

    // API
    case '/api/contact':
        // This handles your fetch request from the contact modal
        $mailer = new ResendMailer();
        $controller = new ContactController($mailer);
        $controller->handleRequest();
        break;
    case '/api/session':
        $controller = new ProjectsController($projectService);
        $controller->session();
        break;
    case '/api/projects':
        $controller = new ProjectsController($projectService);
        $controller->handleRequest();
        break;
    case '/api/public-projects':
        $controller = new PublicProjectsController($projectService);
        $controller->handleRequest();
        break;

    // Auth Actions
    case '/auth/authorize':
        $controller = new AuthController();
        $controller->authorize();
        break;
    case '/auth/callback':
        $controller = new AuthController();
        $controller->callback();
        break;

    // HealthCheck
    case '/ping':
        http_response_code(200);
        echo 'PONG'; 
        exit;
    default: 
        http_response_code(404);
        echo "Page not found.";
        break;
}