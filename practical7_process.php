<?php
/**
 * Practical 7: PHP Form Processing with Server-Side Validation and CSV/JSON File Storage
 * Processes submitted contact/registration form data using PHP.
 * Validates and sanitizes inputs on the server side.
 * Stores records in both CSV and JSON file format.
 * Displays success/error messages.
 */

// Start output buffering
ob_start();

// Define file paths for storage
$csvFile = __DIR__ . '/practical7_data.csv';
$jsonFile = __DIR__ . '/practical7_data.json';

// Initialize error and success messages
$errors = [];
$success = '';

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // -------------------------
    // 1. Retrieve and sanitize inputs
    // -------------------------
    $fullname = isset($_POST['fullname']) ? trim($_POST['fullname']) : '';
    $email    = isset($_POST['email'])    ? trim($_POST['email'])    : '';
    $phone    = isset($_POST['phone'])    ? trim($_POST['phone'])    : '';
    $course   = isset($_POST['course'])   ? trim($_POST['course'])   : '';
    $message  = isset($_POST['message'])  ? trim($_POST['message'])  : '';

    // Sanitize inputs to prevent XSS
    $fullname = htmlspecialchars($fullname, ENT_QUOTES, 'UTF-8');
    $email    = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $phone    = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
    $course   = htmlspecialchars($course, ENT_QUOTES, 'UTF-8');
    $message  = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

    // -------------------------
    // 2. Server-side validation
    // -------------------------

    // Full Name validation
    if (empty($fullname)) {
        $errors[] = 'Full Name is required.';
    } elseif (!preg_match('/^[A-Za-z ]+$/', $fullname)) {
        $errors[] = 'Full Name should contain only letters and spaces.';
    }

    // Email validation
    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    // Phone validation
    if (empty($phone)) {
        $errors[] = 'Phone number is required.';
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $errors[] = 'Phone number must be exactly 10 digits.';
    }

    // Course validation
    $validCourses = ['CE', 'IT', 'EC', 'ME'];
    if (empty($course)) {
        $errors[] = 'Please select a course.';
    } elseif (!in_array($course, $validCourses)) {
        $errors[] = 'Invalid course selected.';
    }

    // Message validation
    if (empty($message)) {
        $errors[] = 'Message is required.';
    } elseif (strlen($message) < 10) {
        $errors[] = 'Message must be at least 10 characters long.';
    }

    // -------------------------
    // 3. Store data if no errors
    // -------------------------
    if (empty($errors)) {

        $timestamp = date('Y-m-d H:i:s');

        // --- CSV Storage ---
        $csvHeader = !file_exists($csvFile);
        $csvHandle = fopen($csvFile, 'a');

        if ($csvHandle) {
            if ($csvHeader) {
                fputcsv($csvHandle, ['Full Name', 'Email', 'Phone', 'Course', 'Message', 'Timestamp']);
            }
            fputcsv($csvHandle, [$fullname, $email, $phone, $course, $message, $timestamp]);
            fclose($csvHandle);
        } else {
            $errors[] = 'Error writing to CSV file.';
        }

        // --- JSON Storage ---
        $jsonData = [];
        if (file_exists($jsonFile)) {
            $existingData = file_get_contents($jsonFile);
            $jsonData = json_decode($existingData, true);
            if (!is_array($jsonData)) {
                $jsonData = [];
            }
        }

        $newRecord = [
            'fullname'  => $fullname,
            'email'     => $email,
            'phone'     => $phone,
            'course'    => $course,
            'message'   => $message,
            'timestamp' => $timestamp
        ];

        $jsonData[] = $newRecord;

        $jsonResult = file_put_contents($jsonFile, json_encode($jsonData, JSON_PRETTY_PRINT));

        if ($jsonResult === false) {
            $errors[] = 'Error writing to JSON file.';
        }

        if (empty($errors)) {
            $success = 'Your form has been submitted successfully! Data saved to CSV and JSON files.';
        }
    }

} else {
    // If someone accesses this page directly via GET
    header('Location: practical7_form.HTML');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Form Submission Result</title>
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
    </style>
</head>
<body>

    <div id="header">
        <h1 align="center">STUDENTHUB</h1>
    </div>

    <br>

    <div align="center">
        <a href="home.HTML"><button type="button">Home</button></a>
        <a href="practical7_form.HTML"><button type="button">Contact Form</button></a>
    </div>

    <br>
    <hr>
    <br>

    <div align="center">

        <h2><u>Form Submission Result</u></h2>

        <br>

        <?php if (!empty($success)): ?>
            <div class="success-msg">
                <p><?php echo $success; ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="error-msg">
                <p>Please fix the following errors:</p>
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?php echo $err; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <br>

        <a href="practical7_form.HTML">
            <button type="button">Go Back to Form</button>
        </a>

        <br><br>

        <a href="practical7_records.php">
            <button type="button">View Stored Records</button>
        </a>

    </div>

    <br>
    <hr>

    <footer align="center">
        <h4>@ 2026 Jaimin Khambhaliya</h4>
    </footer>

</body>
</html>
<?php ob_end_flush(); ?>
