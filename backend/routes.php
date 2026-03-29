<?php

use App\Controllers\AdminController;
use App\Controllers\AdminLogController;
use App\Controllers\AdminProjectController;
use App\Controllers\AdminRateLimitController;
use App\Controllers\AdminUserController;
use App\Controllers\Auth\GitHub\AuthController;
use App\Controllers\Auth\User\UserController;
use App\Controllers\ContactController;
use App\Infrastructure\Mail\ResendMailer;
use App\Controllers\ProjectsController;
use App\Controllers\PublicProjectsController;
use App\Controllers\UserProfileController;
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

// ── Dynamic route pattern matching ───────────────────────────────────────────
// Resolve parameterised paths to a static route key before the switch.
// Order matters: more-specific patterns first.

$adminUserId    = null;
$adminProjectId = null;

// POST /api/admin/users/{id}/resend-verification
if (preg_match('#^/api/admin/users/(\d+)/resend-verification$#', $path, $m)) {
    $adminUserId = (int) $m[1];
    $path = '/api/admin/users/resend-verification';
}

// GET /api/admin/users/{id}
elseif (preg_match('#^/api/admin/users/(\d+)$#', $path, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $adminUserId = (int) $m[1];
    $path = '/api/admin/users/show';
}

// PUT /api/admin/users/{id}
elseif (preg_match('#^/api/admin/users/(\d+)$#', $path, $m) && $_SERVER['REQUEST_METHOD'] === 'PUT') {
    $adminUserId = (int) $m[1];
    $path = '/api/admin/users/update';
}

// DELETE /api/admin/users/{id}
elseif (preg_match('#^/api/admin/users/(\d+)$#', $path, $m) && $_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $adminUserId = (int) $m[1];
    $path = '/api/admin/users/delete';
}

// DELETE /api/admin/projects/{id}
elseif (preg_match('#^/api/admin/projects/(\d+)$#', $path, $m) && $_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $adminProjectId = (int) $m[1];
    $path = '/api/admin/projects/delete';
}

// ── Remember me ──────────────────────────────────────────────────────────────

if (empty($_SESSION['authenticated'])) {
    if (isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
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
            setcookie('remember_token', '', time() - 3600, '/');
        }
    }
}

// ── DB routes ────────────────────────────────────────────────────────────────
// Only these routes trigger PDO instantiation and dependency wiring.

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
    '/api/admin/users/show',
    '/api/admin/users/update',
    '/api/admin/users/delete',
    '/api/admin/users/resend-verification',
    '/api/admin/projects',
    '/api/admin/projects/export',
    '/api/admin/projects/delete',
    '/api/admin/logs',
    '/api/admin/logs/export',
    '/api/admin/rate-limits',
    '/api/admin/rate-limits/export',
    '/api/user/profile',
    '/api/user/password',
];

if (in_array($path, $db_routes)) {
    $pdo = DatabaseConnection::getInstance()->getConnection();
    $projectRepository          = new ProjectRepository($pdo);
    $projectService             = new ProjectService($projectRepository);
    $userRepository             = new UserRepository($pdo);
    $emailVerificationRepository = new EmailVerificationRepository($pdo);
    $passwordResetRepository    = new PasswordResetRepository($pdo);
    $rateLimitRepository        = new RateLimitRepository($pdo);
    $loginActivityRepository    = new LoginActivityRepository($pdo);
    $rateLimiterService         = new RateLimiterService($rateLimitRepository);
    $loginActivityService       = new LoginActivityService($loginActivityRepository);
    $rememberTokenRepository    = new RememberTokenRepository($pdo);
    $rememberTokenService       = new RememberTokenService($rememberTokenRepository, $userRepository);
    $mailer                     = new ResendMailer();
    $userService                = new UserService($userRepository, $emailVerificationRepository, $mailer, $passwordResetRepository);

    $userController          = new UserController($userService, $rateLimiterService, $loginActivityService, $rememberTokenService);
    $adminController         = new AdminController($userRepository, $projectRepository, $loginActivityRepository);
    $adminUserController     = new AdminUserController($userRepository, $emailVerificationRepository, $userService);
    $adminProjectController  = new AdminProjectController($projectRepository);
    $adminLogController      = new AdminLogController($loginActivityRepository);
    $adminRateLimitController = new AdminRateLimitController($rateLimitRepository);
    $userProfileController   = new UserProfileController($userRepository);
}

// ── Router ───────────────────────────────────────────────────────────────────

switch ($path) {

    // ── Public pages ──────────────────────────────────────────────────────────

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
    case '/profile':
        require __DIR__ . '/../frontend/pages/profile.html';
        break;

    // ── Auth pages ────────────────────────────────────────────────────────────

    case '/login':
        require __DIR__ . '/../frontend/pages/login.html';
        break;
    case '/register':
        require __DIR__ . '/../frontend/pages/register.html';
        break;

    // ── Auth API ──────────────────────────────────────────────────────────────

    case '/auth/login':
        $userController->handleLoginRequest();
        break;
    case '/auth/register':
        $userController->handleRegisterRequest();
        break;
    case '/auth/logout':
        session_unset();
        session_destroy();
        header('Location: /auth/login?message=logged_out');
        exit;
    case '/auth/verify':
        $userController->handleVerifyRequest();
        break;
    case '/auth/forgot-password':
        $userController->handleForgotPasswordRequest();
        break;
    case '/auth/reset-password':
        $userController->handleResetPasswordRequest();
        break;
    case '/auth/authorize':
        $controller = new AuthController();
        $controller->authorize();
        break;
    case '/auth/callback':
        $controller = new AuthController();
        $controller->callback();
        break;

    // ── Admin pages ───────────────────────────────────────────────────────────

    case '/admin':
        require __DIR__ . '/../frontend/pages/admin/index.html';
        break;
    case '/admin/edit':
        require __DIR__ . '/../frontend/pages/admin/edit.html';
        break;
    case '/admin/projects':
        require __DIR__ . '/../frontend/pages/admin/projects.html';
        break;
    case '/admin/users':
        require __DIR__ . '/../frontend/pages/admin/users.html';
        break;
    case '/admin/logs':
        require __DIR__ . '/../frontend/pages/admin/logs.html';
        break;
    case '/admin/rate-limits':
        require __DIR__ . '/../frontend/pages/admin/rate-limits.html';
        break;

    // ── Public API ────────────────────────────────────────────────────────────

    case '/api/contact':
        $mailer     = new ResendMailer();
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

    // ── Admin stats API ───────────────────────────────────────────────────────

    case '/api/admin/stats':
        $adminController->stats();
        break;

    // ── Admin users API ───────────────────────────────────────────────────────

    case '/api/admin/users':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $adminUserController->index();
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        }
        break;

    case '/api/admin/users/export':
        $adminUserController->export();
        break;

    case '/api/admin/users/show':
        $adminUserController->show($adminUserId);
        break;

    case '/api/admin/users/update':
        $adminUserController->update($adminUserId);
        break;

    case '/api/admin/users/delete':
        $adminUserController->destroy($adminUserId);
        break;

    case '/api/admin/users/resend-verification':
        $adminUserController->resendVerification($adminUserId);
        break;

    // ── Admin projects API ────────────────────────────────────────────────────

    case '/api/admin/projects':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $adminProjectController->listProjects();
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        }
        break;
    case '/api/admin/projects/export':
        $adminProjectController->exportCsv();
        break;
    case '/api/admin/projects/delete':
        $adminProjectController->deleteProject($adminProjectId);
        break;

    // ── Admin logs API ────────────────────────────────────────────────────────

    case '/api/admin/logs':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $adminLogController->listLogs();
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        }
        break;
    case '/api/admin/logs/export':
        $adminLogController->exportCsv();
        break;

    // ── Admin rate limits API ─────────────────────────────────────────────────

    case '/api/admin/rate-limits':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $adminRateLimitController->listRateLimits();
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        }
        break;
    case '/api/admin/rate-limits/export':
        $adminRateLimitController->exportCsv();
        break;

    // ── User profile API ──────────────────────────────────────────────────────

    case '/api/user/profile':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $userProfileController->getProfile();
        } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            $userProfileController->updateProfile();
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        }
        break;
    case '/api/user/password':
        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            $userProfileController->updatePassword();
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        }
        break;

    // ── Health check ──────────────────────────────────────────────────────────

    case '/ping':
        http_response_code(200);
        echo 'PONG';
        exit;

    // ── 404 ───────────────────────────────────────────────────────────────────

    default:
        http_response_code(404);
        require __DIR__ . '/../frontend/pages/404.html';
        break;
}