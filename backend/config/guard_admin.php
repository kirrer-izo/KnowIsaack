<?php
/**
 * guard_admin.php
 *
 * Protects write-only admin routes (POST, PUT, DELETE on /api/admin/*).
 * Must be required AFTER session_start() has been called in routes.php.
 *
 * Returns:
 *   401 - No session at all (not logged in)
 *   403 - Logged in but not an admin (viewer attempting a write)
 *
 * Usage in routes.php (place before the controller call):
 *   require __DIR__ . '/config/guard_admin.php';
 */

$_guard_session = $_SESSION['db_user'] ?? null;

if (!$_guard_session) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode([
        'status'  => 'error',
        'message' => 'Unauthenticated. Please log in.',
    ]);
    exit;
}

// Safely read role — default to 'viewer' if missing for any reason
if (($_guard_session['role'] ?? 'viewer') !== 'admin') {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode([
        'status'  => 'error',
        'message' => 'Forbidden. This action requires admin privileges.',
    ]);
    exit;
}

unset($_guard_session); // clean up, don't pollute route scope