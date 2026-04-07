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
use App\Domain\Interfaces\EmailServiceInterface;
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

// 1. Safe Session Start: Prevents "Session already started" errors during PHPUnit runs
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$request = $_SERVER['REQUEST_URI'] ?? '/';
$base_path = '/portfolio';
$path = str_replace($base_path, '', $request);
$path = strtok($path, '?');

// ── Dynamic route pattern matching ───────────────────────────────────────────

$adminUserId    = null;
$adminProjectId = null;

if (preg_match('#^/api/admin/users/(\d+)/resend-verification$#', $path, $m)) {
    $adminUserId = (int) $m[1];
    $path = '/api/admin/users/resend-verification';
} elseif (preg_match('#^/api/admin/users/(\d+)$#', $path, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $adminUserId = (int) $m[1];
    $path = '/api/admin/users/show';
} elseif (preg_match('#^/api/admin/users/(\d+)$#', $path, $m) && $_SERVER['REQUEST_METHOD'] === 'PUT') {
    $adminUserId = (int) $m[1];
    $path = '/api/admin/users/update';
} elseif (preg_match('#^/api/admin/users/(\d+)$#', $path, $m) && $_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $adminUserId = (int) $m[1];
    $path = '/api/admin/users/delete';
} elseif (preg_match('#^/api/admin/projects/(\d+)$#', $path, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $adminProjectId = (int) $m[1];
    $path = '/api/admin/projects/show';
} elseif (preg_match('#^/api/admin/projects/(\d+)$#', $path, $m) && $_SERVER['REQUEST_METHOD'] === 'PUT') {
    $adminProjectId = (int) $m[1];
    $path = '/api/admin/projects/update';
} elseif (preg_match('#^/api/admin/projects/(\d+)$#', $path, $m) && $_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $adminProjectId = (int) $m[1];
    $path = '/api/admin/projects/delete';
}

// ── Remember me ──────────────────────────────────────────────────────────────

if (empty($_SESSION['authenticated']) && isset($_COOKIE['remember_token'])) {
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

// ── DB routes ────────────────────────────────────────────────────────────────

$db_routes = [
    '/api/projects', '/api/public-projects', '/api/session', '/auth/login',
    '/auth/register', '/auth/verify', '/auth/forgot-password', '/auth/reset-password',
    '/api/admin/stats', '/api/admin/users', '/api/admin/users/export', '/api/admin/users/show',
    '/api/admin/users/update', '/api/admin/users/delete', '/api/admin/users/resend-verification',
    '/api/admin/projects', '/api/admin/projects/export', '/api/admin/projects/show',
    '/api/admin/projects/update', '/api/admin/projects/delete', '/api/admin/logs',
    '/api/admin/logs/export', '/api/admin/rate-limits', '/api/admin/rate-limits/export',
    '/api/user/profile', '/api/user/password',
];

if (in_array($path, $db_routes)) {
    $pdo = DatabaseConnection::getInstance()->getConnection();
    $projectRepository           = new ProjectRepository($pdo);
    $projectService              = new ProjectService($projectRepository);
    $userRepository              = new UserRepository($pdo);
    $emailVerificationRepository = new EmailVerificationRepository($pdo);
    $passwordResetRepository     = new PasswordResetRepository($pdo);
    $rateLimitRepository         = new RateLimitRepository($pdo);
    $loginActivityRepository     = new LoginActivityRepository($pdo);
    $rateLimiterService          = new RateLimiterService($rateLimitRepository);
    $loginActivityService        = new LoginActivityService($loginActivityRepository);
    $rememberTokenRepository     = new RememberTokenRepository($pdo);
    $rememberTokenService        = new RememberTokenService($rememberTokenRepository, $userRepository);

    // 2. Mocking logic: Use a Null Mailer if testing to avoid SSL/API key errors
    if (isset($GLOBALS['IS_TESTING']) && $GLOBALS['IS_TESTING'] === true) {
        $mailer = new class implements EmailServiceInterface { 
        public function sendEmail($to, $subject, $body, $replyTo = null): bool { 
            return true; 
        }
        public function sendVerificationEmail(string $to, string $name, string $token): bool {
            return true;
        }
        public function sendPasswordResetEmail(string $to, string $name, string $token): bool {
            return true;
        }
    };
    } else {
        $mailer = new ResendMailer();
    }

    $userService = new UserService($userRepository, $emailVerificationRepository, $mailer, $passwordResetRepository);

    $userController           = new UserController($userService, $rateLimiterService, $loginActivityService, $rememberTokenService);
    $adminController          = new AdminController($userRepository, $projectRepository, $loginActivityRepository);
    $adminUserController      = new AdminUserController($userRepository, $emailVerificationRepository, $userService);
    $adminProjectController   = new AdminProjectController($projectRepository);
    $adminLogController       = new AdminLogController($loginActivityRepository);
    $adminRateLimitController = new AdminRateLimitController($rateLimitRepository);
    $userProfileController    = new UserProfileController($userRepository);
}

// ── Admin Guard ──────────────────────────────────────────────────────────────
if (str_starts_with($path, '/admin') || str_starts_with($path, '/api/admin')) {
    require_once __DIR__ . '/config/guard_user.php';
}

// ── Router ───────────────────────────────────────────────────────────────────

switch ($path) {

    // ── Public pages ──────────────────────────────────────────────────────────

    case '/':
    case '/home':
        require __DIR__ . '/../frontend/pages/home.html';
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
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        if (PHP_SAPI !== 'cli') {
            header('Location: /auth/login?message=logged_out');
        }
        terminate();
        break;
    case '/auth/verify':
        $userController->handleVerifyRequest();
        break;
    case '/auth/forgot-password':
        $userController->handleForgotPasswordRequest();
        break;
    case '/auth/reset-password':
        $userController->handleResetPasswordRequest();
        break;

    // ── Admin API ─────────────────────────────────────────────────────────────

    case '/api/admin/stats':
        $adminController->stats();
        break;

    // ── Admin users API ───────────────────────────────────────────────────────

    case '/api/admin/users':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $adminUserController->index();
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $adminUserController->create();
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
            terminate();
        }
        break;

    // ── Admin projects API ────────────────────────────────────────────────────

    case '/api/admin/projects':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $adminProjectController->listProjects();
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $adminProjectController->createProject();
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
            terminate();
        }
        break;

    // ── Health check ──────────────────────────────────────────────────────────

    case '/ping':
        http_response_code(200);
        echo 'PONG';
        terminate();
        break;

    // ── 404 ───────────────────────────────────────────────────────────────────

    default:
        http_response_code(404);
        if (PHP_SAPI !== 'cli') {
            require __DIR__ . '/../frontend/pages/404.html';
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Not Found']);
        }
        terminate();
        break;
}

/**
 * Stops execution only if we are not in a testing environment.
 * 3. Wrapped in function_exists to prevent fatal errors during multiple PHPUnit tests.
 */
if (!function_exists('terminate')) {
    function terminate(): void 
    {
        if (PHP_SAPI !== 'cli') {
            exit;
        }
    }
}