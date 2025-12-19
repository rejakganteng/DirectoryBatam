<?php
// auth.php - simple auth helpers (session-based)
if (session_status() === PHP_SESSION_NONE) session_start();

function currentUser() {
    return isset($_SESSION['user']) ? $_SESSION['user'] : null;
}

function isLoggedIn() {
    return currentUser() !== null;
}

function isAdmin() {
    $u = currentUser();
    return $u && isset($u['role']) && $u['role'] === 'admin';
}

function loginUser($user) {
    // $user should be an associative array with id, name, email, role, avatar
    session_regenerate_id(false); // Regenerate session ID without deleting old one
    $_SESSION['user'] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'] ?? 'user',
        'avatar' => $user['avatar'] ?? null
    ];
}

function logoutUser() {
    unset($_SESSION['user']);
    session_regenerate_id(true);
}

function requireAdmin() {
    if (!isAdmin()) {
        header('HTTP/1.1 403 Forbidden');
        echo '403 Forbidden - Admins only';
        exit;
    }
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}
?>