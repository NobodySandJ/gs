<?php
/**
 * Authentication Check Middleware
 * Include this file at the top of protected pages
 */

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

// Check if user has specific role
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Get current user info
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'full_name' => $_SESSION['full_name'],
        'email' => $_SESSION['email'],
        'role' => $_SESSION['role']
    ];
}

// Require login (redirect if not logged in)
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

// Require admin role
function requireAdmin() {
    requireLogin();
    if (!hasRole(ROLE_ADMIN)) {
        http_response_code(403);
        die('Akses ditolak. Hanya admin yang dapat mengakses halaman ini.');
    }
}

// Update last activity
if (isLoggedIn()) {
    $_SESSION['last_activity'] = time();
    
    // Check session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
        session_unset();
        session_destroy();
        header('Location: ' . BASE_URL . '/auth/login.php?timeout=1');
        exit;
    }
}
?>
