<?php
/**
 * Practical 9: Secure User Registration with Database Insert,
 * Duplicate Email Check, and Password Hashing
 *
 * Features:
 * - Frontend and backend validation
 * - Duplicate email/username check
 * - Password hashing using password_hash()
 * - MySQLi prepared statements
 * - Success/error messages
 */

require_once 'db.php';

$errors = [];
$success = '';
$old = [
    'username' => '',
    'email'    => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Retrieve and sanitize inputs
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email    = isset($_POST['email'])    ? trim($_POST['email'])    : '';
    $password = isset($_POST['password']) ? $_POST['password']       : '';
    $confirm  = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    // Store old values for repopulating form
    $old['username'] = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
    $old['email']    = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');

    // -------------------------
    // Server-side validation
    // -------------------------

    // Username validation
    if (empty($username)) {
        $errors[] = 'Username is required.';
    } elseif (!preg_match('/^[A-Za-z ]{3,100}$/', $username)) {
        $errors[] = 'Username must contain only letters and spaces (3-100 characters).';
    }

    // Email validation
    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    // Password validation
    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter.';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number.';
    }

    // Confirm password
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    // -------------------------
    // Check for duplicate email
    // -------------------------
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = 'This email is already registered. Please use a different email.';
        }
        $stmt->close();
    }

    // -------------------------
    // Check for duplicate username
    // -------------------------
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = 'This username is already taken. Please choose a different one.';
        }
        $stmt->close();
    }

    // -------------------------
    // Insert user into database
    // -------------------------
    if (empty($errors)) {
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'student')");
        $stmt->bind_param("sss", $username, $email, $hashedPassword);

        if ($stmt->execute()) {
            $success = 'Registration successful! You can now login.';
            $old = ['username' => '', 'email' => ''];

            // Log the registration in audit_logs
            $userId = $stmt->insert_id;
            $action = 'REGISTER';
            $details = "New user registered: $username ($email)";
            $ip = $_SERVER['REMOTE_ADDR'];
            $logStmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
            $logStmt->bind_param("isss", $userId, $action, $details, $ip);
            $logStmt->execute();
            $logStmt->close();
        } else {
            $errors[] = 'Registration failed. Please try again.';
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - User Registration</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .success-msg {
            color: green;
            font-weight: bold;
            background-color: #d4edda;
            border: 2px solid green;
            padding: 12px;
            border-radius: 8px;
            margin: 10px auto;
            max-width: 500px;
        }
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
        .error-msg ul {
            text-align: left;
            margin: 5px 0;
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

    <br>

    <div align="center">
        <a href="home.HTML"><button type="button">Home</button></a>
        <a href="practical10_login.php"><button type="button">Login</button></a>
    </div>

    <br>
    <hr>
    <br>

    <div align="center">

        <?php if (!empty($success)): ?>
            <div class="success-msg">
                <p><?php echo $success; ?></p>
            </div>
            <br>
            <a href="practical10_login.php"><button type="button">Go to Login</button></a>
            <br><br>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="error-msg">
                <p>Please fix the following errors:</p>
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <br>
        <?php endif; ?>

        <form action="practical9_register.php" method="POST" class="card" id="registerForm">

            <h2 align="center"><u>User Registration (Practical 9)</u></h2>

            <br>

            <table class="form-table" align="center">

                <tr>
                    <td><label for="username">Username :</label></td>
                    <td>
                        <input type="text" id="username" name="username"
                            placeholder="Enter your name"
                            value="<?php echo $old['username']; ?>"
                        >
                        <small id="usernameError" class="field-error"></small>
                    </td>
                </tr>

                <tr>
                    <td><label for="email">Email :</label></td>
                    <td>
                        <input type="email" id="email" name="email"
                            placeholder="abc@gmail.com"
                            value="<?php echo $old['email']; ?>"
                        >
                        <small id="emailError" class="field-error"></small>
                    </td>
                </tr>

                <tr>
                    <td><label for="password">Password :</label></td>
                    <td>
                        <input type="password" id="password" name="password"
                            placeholder="Min 8 chars, 1 uppercase, 1 number"
                        >
                        <small id="passwordError" class="field-error"></small>
                    </td>
                </tr>

                <tr>
                    <td><label for="confirm_password">Confirm Password :</label></td>
                    <td>
                        <input type="password" id="confirm_password" name="confirm_password"
                            placeholder="Re-enter password"
                        >
                        <small id="confirmError" class="field-error"></small>
                    </td>
                </tr>

            </table>

            <br>

            <div align="center">
                <button type="submit">Register</button>
                <button type="reset">Reset</button>
            </div>

            <br>

        </form>

        <br>

        <p>Already have an account? <a href="practical10_login.php">Login here</a></p>

        <br>

        <a href="home.HTML">
            <button type="button">BACK</button>
        </a>

    </div>

    <br>
    <hr>

    <footer align="center">
        <h4>@ 2026 Jaimin Khambhaliya</h4>
    </footer>

    <script>
    // Frontend validation (consistent with backend)
    const registerForm = document.getElementById('registerForm');

    registerForm.addEventListener('submit', function(event) {
        let valid = true;

        document.getElementById('usernameError').innerText = '';
        document.getElementById('emailError').innerText = '';
        document.getElementById('passwordError').innerText = '';
        document.getElementById('confirmError').innerText = '';

        const username = document.getElementById('username').value.trim();
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        const confirm = document.getElementById('confirm_password').value;

        const namePattern = /^[A-Za-z ]{3,100}$/;
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (username === '') {
            document.getElementById('usernameError').innerText = 'Username is required.';
            valid = false;
        } else if (!namePattern.test(username)) {
            document.getElementById('usernameError').innerText = 'Only letters and spaces (3-100 chars).';
            valid = false;
        }

        if (email === '') {
            document.getElementById('emailError').innerText = 'Email is required.';
            valid = false;
        } else if (!emailPattern.test(email)) {
            document.getElementById('emailError').innerText = 'Please enter a valid email.';
            valid = false;
        }

        if (password === '') {
            document.getElementById('passwordError').innerText = 'Password is required.';
            valid = false;
        } else if (password.length < 8) {
            document.getElementById('passwordError').innerText = 'Min 8 characters required.';
            valid = false;
        } else if (!/[A-Z]/.test(password)) {
            document.getElementById('passwordError').innerText = 'Must contain an uppercase letter.';
            valid = false;
        } else if (!/[0-9]/.test(password)) {
            document.getElementById('passwordError').innerText = 'Must contain a number.';
            valid = false;
        }

        if (password !== confirm) {
            document.getElementById('confirmError').innerText = 'Passwords do not match.';
            valid = false;
        }

        if (!valid) {
            event.preventDefault();
        }
    });
    </script>

</body>
</html>
<?php $conn->close(); ?>
