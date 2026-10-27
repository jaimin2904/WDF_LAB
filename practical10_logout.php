<?php
/**
 * Practical 10: Logout Script
 * Destroys session and redirects to login page.
 */

session_start();
require_once 'db.php';

// Log the logout action
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $action = 'LOGOUT';
    $details = "User logged out: " . $_SESSION['username'];
    $ip = $_SERVER['REMOTE_ADDR'];
    $logStmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $logStmt->bind_param("isss", $userId, $action, $details, $ip);
    $logStmt->execute();
    $logStmt->close();
}

$conn->close();

// Destroy session
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

header('Location: practical10_login.php?logout=1');
exit;
?>
