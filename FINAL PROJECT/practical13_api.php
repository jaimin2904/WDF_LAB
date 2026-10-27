<?php
/**
 * Practical 13: PHP JSON API Endpoint for Students CRUD
 * 
 * Features:
 * - REST-like JSON API with standard HTTP status codes (200, 201, 400, 404, 409, 500)
 * - Actions: list, get, create, update, delete, stats
 * - Server-side input validation and duplicate checking
 * - MySQLi prepared statements for SQL injection prevention
 * - Centralized response helper and error handling
 * - Audit logging support
 */

// Set response headers
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Start session if not started (to record audit log if logged in)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';

/**
 * Standardized JSON Response Helper
 */
function sendResponse($status, $statusCode, $message, $data = null) {
    http_response_code($statusCode);
    echo json_encode([
        'status'    => $status,
        'code'      => $statusCode,
        'message'   => $message,
        'data'      => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Audit log helper
 */
function logAction($conn, $action, $details) {
    $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("isss", $userId, $action, $details, $ip);
        $stmt->execute();
        $stmt->close();
    }
}

// Read raw JSON input if sent as application/json
$rawInput = file_get_contents('php://input');
$jsonInput = json_decode($rawInput, true);

// Merge JSON input with $_POST and $_GET
$requestData = array_merge($_GET, $_POST, is_array($jsonInput) ? $jsonInput : []);

// Determine action and method
$action = isset($requestData['action']) ? trim($requestData['action']) : '';
$method = $_SERVER['REQUEST_METHOD'];

// Route actions
try {
    switch ($action) {

        // -------------------------------------------------------------
        // 1. LIST STUDENTS (with optional search and course filter)
        // -------------------------------------------------------------
        case 'list':
            $search = isset($requestData['search']) ? trim($requestData['search']) : '';
            $course = isset($requestData['course']) ? trim($requestData['course']) : '';

            $sql = "SELECT id, fullname, email, phone, course, year, gender, address, status, created_at 
                    FROM students 
                    WHERE status != 'deleted'";
            $params = [];
            $types = "";

            if (!empty($search)) {
                $sql .= " AND (fullname LIKE ? OR email LIKE ? OR phone LIKE ?)";
                $searchWildcard = "%" . $search . "%";
                $params[] = $searchWildcard;
                $params[] = $searchWildcard;
                $params[] = $searchWildcard;
                $types .= "sss";
            }

            if (!empty($course) && $course !== 'all') {
                $sql .= " AND course = ?";
                $params[] = $course;
                $types .= "s";
            }

            $sql .= " ORDER BY id DESC";

            $stmt = $conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();

            $students = [];
            while ($row = $result->fetch_assoc()) {
                $students[] = $row;
            }
            $stmt->close();

            sendResponse('success', 200, 'Students retrieved successfully', [
                'count' => count($students),
                'students' => $students
            ]);
            break;

        // -------------------------------------------------------------
        // 2. GET SINGLE STUDENT BY ID
        // -------------------------------------------------------------
        case 'get':
            $id = isset($requestData['id']) ? (int)$requestData['id'] : 0;
            if ($id <= 0) {
                sendResponse('error', 400, 'Valid Student ID is required');
            }

            $stmt = $conn->prepare("SELECT id, fullname, email, phone, course, year, gender, address, status, created_at FROM students WHERE id = ? AND status != 'deleted'");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                $stmt->close();
                sendResponse('error', 404, 'Student not found');
            }

            $student = $result->fetch_assoc();
            $stmt->close();

            sendResponse('success', 200, 'Student details retrieved', $student);
            break;

        // -------------------------------------------------------------
        // 3. CREATE NEW STUDENT
        // -------------------------------------------------------------
        case 'create':
            if ($method !== 'POST') {
                sendResponse('error', 405, 'HTTP Method Not Allowed. Use POST.');
            }

            $fullname = trim($requestData['fullname'] ?? '');
            $email    = trim($requestData['email'] ?? '');
            $phone    = trim($requestData['phone'] ?? '');
            $course   = trim($requestData['course'] ?? '');
            $year     = isset($requestData['year']) ? (int)$requestData['year'] : 0;
            $gender   = trim($requestData['gender'] ?? '');
            $address  = trim($requestData['address'] ?? '');

            // Validation
            $errors = [];
            if (empty($fullname) || strlen($fullname) < 3) {
                $errors[] = 'Full name is required (min 3 characters).';
            }
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'A valid email address is required.';
            }
            if (empty($phone) || !preg_match('/^[0-9]{10}$/', $phone)) {
                $errors[] = 'Phone must be exactly 10 digits.';
            }
            if (empty($course)) {
                $errors[] = 'Course selection is required.';
            }
            if ($year < 1 || $year > 4) {
                $errors[] = 'Year must be between 1 and 4.';
            }
            if (!in_array($gender, ['Male', 'Female', 'Other'])) {
                $errors[] = 'Please select a valid gender.';
            }

            if (!empty($errors)) {
                sendResponse('error', 400, 'Validation failed: ' . implode(' ', $errors), ['errors' => $errors]);
            }

            // Check duplicate email
            $checkStmt = $conn->prepare("SELECT id FROM students WHERE email = ? AND status != 'deleted'");
            $checkStmt->bind_param("s", $email);
            $checkStmt->execute();
            $checkStmt->store_result();
            if ($checkStmt->num_rows > 0) {
                $checkStmt->close();
                sendResponse('error', 409, 'A student with this email address already exists.');
            }
            $checkStmt->close();

            // Insert new student
            $insertStmt = $conn->prepare("INSERT INTO students (fullname, email, phone, course, year, gender, address) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $insertStmt->bind_param("ssssiss", $fullname, $email, $phone, $course, $year, $gender, $address);

            if ($insertStmt->execute()) {
                $newId = $conn->insert_id;
                $insertStmt->close();

                logAction($conn, 'AJAX_CREATE_STUDENT', "Created student: $fullname ($email), ID: $newId");

                sendResponse('success', 201, 'Student created successfully', [
                    'id'       => $newId,
                    'fullname' => $fullname,
                    'email'    => $email,
                    'phone'    => $phone,
                    'course'   => $course,
                    'year'     => $year,
                    'gender'   => $gender
                ]);
            } else {
                $insertStmt->close();
                sendResponse('error', 500, 'Database error: unable to create student');
            }
            break;

        // -------------------------------------------------------------
        // 4. UPDATE STUDENT
        // -------------------------------------------------------------
        case 'update':
            if ($method !== 'POST' && $method !== 'PUT') {
                sendResponse('error', 405, 'HTTP Method Not Allowed. Use POST.');
            }

            $id       = isset($requestData['id']) ? (int)$requestData['id'] : 0;
            $fullname = trim($requestData['fullname'] ?? '');
            $email    = trim($requestData['email'] ?? '');
            $phone    = trim($requestData['phone'] ?? '');
            $course   = trim($requestData['course'] ?? '');
            $year     = isset($requestData['year']) ? (int)$requestData['year'] : 0;
            $gender   = trim($requestData['gender'] ?? '');
            $address  = trim($requestData['address'] ?? '');

            if ($id <= 0) {
                sendResponse('error', 400, 'Valid Student ID is required.');
            }

            // Validation
            $errors = [];
            if (empty($fullname) || strlen($fullname) < 3) {
                $errors[] = 'Full name is required (min 3 characters).';
            }
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'A valid email address is required.';
            }
            if (empty($phone) || !preg_match('/^[0-9]{10}$/', $phone)) {
                $errors[] = 'Phone must be exactly 10 digits.';
            }
            if (empty($course)) {
                $errors[] = 'Course selection is required.';
            }
            if ($year < 1 || $year > 4) {
                $errors[] = 'Year must be between 1 and 4.';
            }
            if (!in_array($gender, ['Male', 'Female', 'Other'])) {
                $errors[] = 'Please select a valid gender.';
            }

            if (!empty($errors)) {
                sendResponse('error', 400, 'Validation failed: ' . implode(' ', $errors), ['errors' => $errors]);
            }

            // Check if record exists
            $checkExist = $conn->prepare("SELECT id FROM students WHERE id = ? AND status != 'deleted'");
            $checkExist->bind_param("i", $id);
            $checkExist->execute();
            if ($checkExist->get_result()->num_rows === 0) {
                $checkExist->close();
                sendResponse('error', 404, 'Student not found.');
            }
            $checkExist->close();

            // Duplicate email check excluding this ID
            $checkEmail = $conn->prepare("SELECT id FROM students WHERE email = ? AND id != ? AND status != 'deleted'");
            $checkEmail->bind_param("si", $email, $id);
            $checkEmail->execute();
            if ($checkEmail->get_result()->num_rows > 0) {
                $checkEmail->close();
                sendResponse('error', 409, 'Another student is already registered with this email.');
            }
            $checkEmail->close();

            // Update record
            $updateStmt = $conn->prepare("UPDATE students SET fullname = ?, email = ?, phone = ?, course = ?, year = ?, gender = ?, address = ? WHERE id = ?");
            $updateStmt->bind_param("ssssissi", $fullname, $email, $phone, $course, $year, $gender, $address, $id);

            if ($updateStmt->execute()) {
                $updateStmt->close();
                logAction($conn, 'AJAX_UPDATE_STUDENT', "Updated student ID: $id ($fullname)");
                sendResponse('success', 200, 'Student updated successfully', [
                    'id'       => $id,
                    'fullname' => $fullname,
                    'email'    => $email,
                    'phone'    => $phone,
                    'course'   => $course,
                    'year'     => $year,
                    'gender'   => $gender
                ]);
            } else {
                $updateStmt->close();
                sendResponse('error', 500, 'Database error: unable to update student');
            }
            break;

        // -------------------------------------------------------------
        // 5. DELETE STUDENT (Soft Delete)
        // -------------------------------------------------------------
        case 'delete':
            $id = isset($requestData['id']) ? (int)$requestData['id'] : 0;
            if ($id <= 0) {
                sendResponse('error', 400, 'Valid Student ID is required.');
            }

            $stmt = $conn->prepare("UPDATE students SET status = 'deleted' WHERE id = ? AND status != 'deleted'");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $stmt->close();
                logAction($conn, 'AJAX_DELETE_STUDENT', "Deleted student ID: $id");
                sendResponse('success', 200, "Student with ID $id deleted successfully.");
            } else {
                $stmt->close();
                sendResponse('error', 404, "Student with ID $id not found or already deleted.");
            }
            break;

        // -------------------------------------------------------------
        // 6. STATS & COURSES SUMMARY
        // -------------------------------------------------------------
        case 'stats':
            $totalResult = $conn->query("SELECT COUNT(*) AS total FROM students WHERE status != 'deleted'");
            $total = $totalResult->fetch_assoc()['total'];

            $courseResult = $conn->query("SELECT course, COUNT(*) AS count FROM students WHERE status != 'deleted' GROUP BY course");
            $courses = [];
            while ($cRow = $courseResult->fetch_assoc()) {
                $courses[] = $cRow;
            }

            sendResponse('success', 200, 'Stats retrieved', [
                'total_students' => (int)$total,
                'course_breakdown' => $courses
            ]);
            break;

        default:
            sendResponse('error', 400, "Invalid or missing 'action' parameter. Supported: list, get, create, update, delete, stats.");
            break;
    }
} catch (Exception $e) {
    sendResponse('error', 500, 'Server Exception: ' . $e->getMessage());
}
