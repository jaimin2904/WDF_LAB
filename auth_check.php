<?php
/**
 * Practical 10: Session Authentication Check
 * Include this file at the top of protected pages.
 * Handles session timeout (30 minutes) and unauthorized access blocking.
 */

session_start();

// Session timeout in seconds (30 minutes)
define('SESSION_TIMEOUT', 1800);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: practical10_login.php?unauthorized=1');
    exit;
}

// Check session timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    // Session expired
    $_SESSION = [];
    session_destroy();
    header('Location: practical10_login.php?timeout=1');
    exit;
}

// Update last activity time
$_SESSION['last_activity'] = time();
?>
