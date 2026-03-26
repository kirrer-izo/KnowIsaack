<?php

use App\Controllers\AdminController;
use App\Controllers\AdminUserController;
use App\Controllers\Auth\GitHub\AuthController;
use App\Controllers\Auth\User\UserController;
use App\Controllers\ContactController;
use App\Infrastructure\Mail\ResendMailer;
use App\Controllers\ProjectsController;
use App\Controllers\PublicProjectsController;
use App\Infrastructure\Database\DatabaseConnection;
use App\Infrastructure\Database\EmailVerificationRepository;
use App\Infrastructure\Database\LoginActivityRepository;
use App\Infrastructure\Database\PasswordResetRepository;
use App\Infrastructure\Database\ProjectRepository;
use App\Infrastructure\Database\RateLimitRepository;
use App\Infrastructure\Database\RememberTokenRepository;
use App\Infrastructure\Database\UserRepository;
use App\Services\LoginActivityService;
use App\Services\ProjectService;
use App\Services\UserService;
use App\Services\RateLimiterService;
use App\Services\RememberTokenService;

session_start();

$request = $_SERVER['REQUEST_URI'];

// Remove subdirectory prefix from path if running in a subdirectory
$base_path = '/portfolio';
$path = str_replace($base_path, '', $request);

// Strip query string e.g. ?code=xxx&state=yyy — we only need the path
$path = strtok($path, '?');

// Extract user ID for admin user actions
$adminUserId = null;
if (preg_match('#^/api/admin/users/(\d+)/resend-verification$#', $path, $matches)) {
    $adminUserId = (int) $matches[1];
    $path = '/api/admin/users/resend-verification';
}
if (preg_match('#^/api/admin/users/(\d+)$#', $path, $matches) && $_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $adminUserId = (int) $matches[1];
    $path = '/api/admin/users/delete';
}


// Remember me: if no session, try the cookie
if (empty($_SESSION['authenticated'])) {
    if (isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        // We need PDO – we can get it here, but only if we connect.
        // However, connecting for every request is heavy if no token.
        // We'll connect only if the cookie exists.
        $pdo = DatabaseConnection::getInstance()->getConnection();
        $rememberTokenRepository = new RememberTokenRepository($pdo);
        $userRepo = new UserRepository($pdo);
        $rememberTokenService = new RememberTokenService($rememberTokenRepository, $userRepo);
        $user = $rememberTokenService->validateToken($token);
        if ($user) {
            session_regenerate_id(true);
            $_SESSION['authenticated'] = true;
            $_SESSION['db_user'] = $user;
        } else {
            // Invalid/expired token: delete the cookie
            setcookie('remember_token', '', time() - 3600, '/');
        }
    }
}

// Routes that require a database connection
// Only these routes trigger PDO instantiation and dependency wiring
$db_routes = [
    '/api/projects', 
    '/api/public-projects', 
    '/api/session',
    '/auth/login',
    '/auth/register',
    '/auth/verify',
    '/auth/forgot-password',
    '/auth/reset-password',
    '/api/admin/stats',
    '/api/admin/users',
    '/api/admin/users/export',
    '/api/admin/users/resend-verification',
    '/api/admin/users/delete',  
];

// Wire up database dependencies only when needed
if (in_array($path, $db_routes)) {
    $pdo = DatabaseConnection::getInstance()->getConnection();
    $projectRepository = new ProjectRepository($pdo);
    $projectService = new ProjectService($projectRepository);

    $userRepository = new UserRepository($pdo);
    $emailVerificationRepository = new EmailVerificationRepository($pdo);
    $passwordResetRepository = new PasswordResetRepository($pdo);
    $rateLimitRepository = new RateLimitRepository($pdo);
    $loginActivityRepository = new LoginActivityRepository($pdo);
    $rateLimiterService = new RateLimiterService($rateLimitRepository);
    $loginActivityService = new LoginActivityService($loginActivityRepository);
    $rememberTokenRepository = new RememberTokenRepository($pdo);
    $rememberTokenService = new RememberTokenService($rememberTokenRepository, $userRepository);

    $mailer = new ResendMailer();

    $userService = new UserService($userRepository, $emailVerificationRepository, $mailer, $passwordResetRepository);

    $userController = new UserController($userService, $rateLimiterService, $loginActivityService, $rememberTokenService);
    $adminController = new AdminController($userRepository, $projectRepository, $loginActivityRepository);
    $adminUserController = new AdminUserController($userRepository,$userService);
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
    case '/auth/forgot-password':
        $userController->handleForgotPasswordRequest();
    break;
    case '/auth/reset-password':
        $userController->handleResetPasswordRequest();
    break;

    
    // Admin Pages — protected by guard inside each controller
    case '/admin':
        require_once __DIR__ . '/config/guard_user.php';
        require __DIR__. '/../frontend/pages/admin/index.html';
        break;
    case '/admin/edit':
        require_once __DIR__ . '/config/guard_user.php';
        require __DIR__ . '/../frontend/pages/admin/edit.html';
        break;
    case '/admin/projects':
        require_once __DIR__ . '/config/guard_user.php';
        echo "Project list - under construction";
        break;
    case '/admin/users':
        require_once __DIR__ . '/config/guard_user.php';
        echo "Users list under construction";
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
    case '/api/admin/stats':
        require_once __DIR__ . '/config/guard_user.php';
        $adminController->stats();
        break;
    case '/api/admin/users':
        require_once __DIR__ . '/config/guard.php';
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $adminUserController->listUsers();
        } else {
            http_response_code(405);
            echo 'Method Not Allowed';
        }
        break;
    case '/api/admin/users/export':
        require_once __DIR__ . '/config/guard.php';
        $adminUserController->exportCsv();
        break;
    case '/api/admin/users/resend-verification':
    require_once __DIR__ . '/config/guard.php';
    $adminUserController->resendVerification($adminUserId);
    break;
    case '/api/admin/users/delete':
        require_once __DIR__ . '/config/guard.php';
        $adminUserController->deleteUser($adminUserId);
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