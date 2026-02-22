<?php 

use App\Controllers\ContactController;
use App\Infrastructure\Mail\ResendMailer;
use App\Controllers\AuthController;
use App\Controllers\ProjectsController;
use App\Controllers\PublicProjectsController;

session_start();

$request = $_SERVER['REQUEST_URI'];

//  Remove subdirectory from path if you are using one
$base_path = '/portfolio';
$path = str_replace($base_path, '', $request);

// Strips ?code=xxx&state=yyy
$path = strtok($path, '?');

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
        require __DIR__. '/../frontend/pages/login.html';
        break;
    
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
        $controller = new ProjectsController();
        $controller->session();
        break;
    case '/api/projects':
        $controller = new ProjectsController();
        $controller->handleRequest();
        break;
    case '/api/public-projects':
        $controller = new PublicProjectsController();
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
    case '/auth/logout':
        $controller = new AuthController();
        $controller->logout();
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