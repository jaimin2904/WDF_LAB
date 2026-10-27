<?php
/**
 * Practical 10: Secure Login with Sessions, Role-Based Access,
 * Timeout, and Remember-Me Token
 *
 * Features:
 * - password_verify() for authentication
 * - Session regeneration after login
 * - Role-based redirection (admin/student)
 * - Session timeout (30 minutes)
 * - Last-login timestamp update
 * - Audit logging
 */

session_start();
require_once 'db.php';

// If already logged in, redirect based on role
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: practical14_dashboard.php');
    } else {
        header('Location: practical10_student_dashboard.php');
    }
    exit;
}

$errors = [];
$old_email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = isset($_POST['email'])    ? trim($_POST['email'])    : '';
    $password = isset($_POST['password']) ? $_POST['password']       : '';

    $old_email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');

    // Validation
    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($password)) {
        $errors[] = 'Password is required.';
    }

    // Authenticate
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id, username, email, password, role, status FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Check if account is active
            if ($user['status'] !== 'active') {
                $errors[] = 'Your account is inactive. Please contact the administrator.';
            }
            // Verify password
            elseif (password_verify($password, $user['password'])) {

                // Regenerate session ID for security
                session_regenerate_id(true);

                // Set session variables
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['username']   = $user['username'];
                $_SESSION['email']      = $user['email'];
                $_SESSION['role']       = $user['role'];
                $_SESSION['login_time'] = time();
                $_SESSION['last_activity'] = time();

                // Update last_login timestamp
                $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $updateStmt->bind_param("i", $user['id']);
                $updateStmt->execute();
                $updateStmt->close();

                // Log the login
                $action = 'LOGIN';
                $details = "User logged in: " . $user['username'] . " (" . $user['email'] . ")";
                $ip = $_SERVER['REMOTE_ADDR'];
                $logStmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
                $logStmt->bind_param("isss", $user['id'], $action, $details, $ip);
                $logStmt->execute();
                $logStmt->close();

                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header('Location: practical14_dashboard.php');
                } else {
                    header('Location: practical10_student_dashboard.php');
                }
                exit;

            } else {
                $errors[] = 'Invalid password. Please try again.';
            }
        } else {
            $errors[] = 'No account found with this email.';
        }

        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Login</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .error-msg {
            color: red;
            font-weight: bold;
            background-color: #f8d7da;
            border: 2px solid red;
            padding: 12px;
            border-radius: 8px;
            margin: 10px auto;
            max-width: 500px;
        }
        .error-msg ul { text-align: left; margin: 5px 0; }
        .info-msg {
            color: #0c5460;
            background-color: #d1ecf1;
            border: 2px solid #0c5460;
            padding: 12px;
            border-radius: 8px;
            margin: 10px auto;
            max-width: 500px;
            font-weight: bold;
        }
        small.field-error {
            color: red;
            display: block;
            margin-top: 2px;
        }
    </style>
</head>
<body>

    <div id="header">
        <h1 align="center">STUDENTHUB</h1>
    </div>

    <br><br>

    <div align="center">

        <?php if (isset($_GET['timeout'])): ?>
            <div class="info-msg">
                <p>Your session has expired due to inactivity. Please login again.</p>
            </div>
            <br>
        <?php endif; ?>

        <?php if (isset($_GET['logout'])): ?>
            <div class="info-msg">
                <p>You have been logged out successfully.</p>
            </div>
            <br>
        <?php endif; ?>

        <?php if (isset($_GET['unauthorized'])): ?>
            <div class="error-msg">
                <p>⚠️ Access denied. Please login to continue.</p>
            </div>
            <br>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="error-msg">
                <p>Login Failed:</p>
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <br>
        <?php endif; ?>

        <form action="practical10_login.php" method="POST" class="card" id="loginForm">

            <br>

            <u><h3>Secure Login (Practical 10)</h3></u>

            <br>

            <table class="form-table" align="center">

                <tr>
                    <td><label for="email">Email :</label></td>
                    <td>
                        <input type="email" id="email" name="email"
                            placeholder="Enter your email"
                            value="<?php echo $old_email; ?>"
                        >
                        <small id="emailError" class="field-error"></small>
                    </td>
                </tr>

                <tr>
                    <td><label for="password">Password :</label></td>
                    <td>
                        <input type="password" id="password" name="password"
                            placeholder="Enter your password"
                        >
                        <small id="passwordError" class="field-error"></small>
                    </td>
                </tr>

            </table>

            <br><br>

            <input type="submit" value="Login" style="width: 250px;">

            <br><br>

            <a href="practical9_register.php">
                <button type="button">Register</button>
            </a>

        </form>

        <br>

        <p>Don't have an account? <a href="practical9_register.php">Register here</a></p>

        <br>

        <div>
            <p><strong>Test Credentials:</strong></p>
            <p>Admin: admin@studenthub.com / password123</p>
            <p>Student: jaimin@gmail.com / password123</p>
        </div>

    </div>

    <br>
    <hr>

    <footer align="center">
        <h4>@ 2026 Jaimin Khambhaliya</h4>
    </footer>

    <script>
    const loginForm = document.getElementById('loginForm');

    loginForm.addEventListener('submit', function(event) {
        let valid = true;

        document.getElementById('emailError').innerText = '';
        document.getElementById('passwordError').innerText = '';

        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (email === '') {
            document.getElementById('emailError').innerText = 'Please enter your email.';
            valid = false;
        } else if (!emailPattern.test(email)) {
            document.getElementById('emailError').innerText = 'Please enter a valid email.';
            valid = false;
        }

        if (password === '') {
            document.getElementById('passwordError').innerText = 'Please enter your password.';
            valid = false;
        }

        if (!valid) {
            event.preventDefault();
        }
    });
    </script>

</body>
</html>
