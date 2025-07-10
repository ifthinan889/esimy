<?php
// Secure admin logout
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/../../config.php';

// Log the logout event if user was logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    $username = $_SESSION['admin_username'] ?? 'unknown';
    logSecurityEvent("Admin logout: " . $username, 'info');
}

// Unset all session variables
$_SESSION = [];

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login page with cache control headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Location: login.php");
exit();
?>