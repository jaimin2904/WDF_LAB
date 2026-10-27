<?php
/**
 * Practical 11: Student Management CRUD with Search and Filter
 *
 * Features:
 * - Add, View, Edit, Delete student records
 * - Search by name/email/course
 * - Filter by course
 * - Delete confirmation
 * - Success/failure messages
 * - MySQLi prepared statements
 * - Soft delete using status column
 * - Export to CSV
 */

require_once 'auth_check.php';
require_once 'db.php';

$message = '';
$messageType = '';

// -------------------------
// Handle DELETE request
// -------------------------
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];

    // Soft delete - set status to 'deleted'
    $stmt = $conn->prepare("UPDATE students SET status = 'deleted' WHERE id = ?");
    $stmt->bind_param("i", $deleteId);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $message = 'Student record deleted successfully.';
        $messageType = 'success';

        // Audit log
        $action = 'DELETE_STUDENT';
        $details = "Soft deleted student ID: $deleteId";
        $ip = $_SERVER['REMOTE_ADDR'];
        $logStmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $logStmt->bind_param("isss", $_SESSION['user_id'], $action, $details, $ip);
        $logStmt->execute();
        $logStmt->close();
    } else {
        $message = 'Failed to delete student record.';
        $messageType = 'error';
    }
    $stmt->close();
}

// -------------------------
// Handle ADD request
// -------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $fullname = trim($_POST['fullname']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $course   = trim($_POST['course']);
    $year     = (int)$_POST['year'];
    $gender   = trim($_POST['gender']);
    $address  = trim($_POST['address']);

    $errors = [];

    if (empty($fullname) || !preg_match('/^[A-Za-z ]+$/', $fullname)) $errors[] = 'Valid full name is required.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (empty($phone) || !preg_match('/^[0-9]{10}$/', $phone)) $errors[] = 'Valid 10-digit phone is required.';
    if (empty($course)) $errors[] = 'Course is required.';
    if ($year < 1 || $year > 4) $errors[] = 'Year must be between 1 and 4.';
    if (!in_array($gender, ['Male', 'Female', 'Other'])) $errors[] = 'Valid gender is required.';

    // Check duplicate email
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM students WHERE email = ? AND status = 'active'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = 'A student with this email already exists.';
        }
        $stmt->close();
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO students (fullname, email, phone, course, year, gender, address) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $fullname, $email, $phone, $course, $year, $gender, $address);

        if ($stmt->execute()) {
            $message = 'Student added successfully!';
            $messageType = 'success';

            // Audit log
            $action = 'ADD_STUDENT';
            $details = "Added student: $fullname ($email)";
            $ip = $_SERVER['REMOTE_ADDR'];
            $logStmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
            $logStmt->bind_param("isss", $_SESSION['user_id'], $action, $details, $ip);
            $logStmt->execute();
            $logStmt->close();
        } else {
            $message = 'Failed to add student.';
            $messageType = 'error';
        }
        $stmt->close();
    } else {
        $message = implode(' ', $errors);
        $messageType = 'error';
    }
}

// -------------------------
// Handle EDIT request
// -------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $editId   = (int)$_POST['edit_id'];
    $fullname = trim($_POST['fullname']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $course   = trim($_POST['course']);
    $year     = (int)$_POST['year'];
    $gender   = trim($_POST['gender']);
    $address  = trim($_POST['address']);

    $errors = [];

    if (empty($fullname) || !preg_match('/^[A-Za-z ]+$/', $fullname)) $errors[] = 'Valid full name is required.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (empty($phone) || !preg_match('/^[0-9]{10}$/', $phone)) $errors[] = 'Valid 10-digit phone is required.';
    if (empty($course)) $errors[] = 'Course is required.';
    if ($year < 1 || $year > 4) $errors[] = 'Year must be between 1 and 4.';
    if (!in_array($gender, ['Male', 'Female', 'Other'])) $errors[] = 'Valid gender is required.';

    // Check duplicate email (exclude current student)
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM students WHERE email = ? AND id != ? AND status = 'active'");
        $stmt->bind_param("si", $email, $editId);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = 'Another student with this email already exists.';
        }
        $stmt->close();
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE students SET fullname=?, email=?, phone=?, course=?, year=?, gender=?, address=? WHERE id=?");
        $stmt->bind_param("sssssssi", $fullname, $email, $phone, $course, $year, $gender, $address, $editId);

        if ($stmt->execute()) {
            $message = 'Student updated successfully!';
            $messageType = 'success';

            // Audit log
            $action = 'UPDATE_STUDENT';
            $details = "Updated student ID $editId: $fullname ($email)";
            $ip = $_SERVER['REMOTE_ADDR'];
            $logStmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
            $logStmt->bind_param("isss", $_SESSION['user_id'], $action, $details, $ip);
            $logStmt->execute();
            $logStmt->close();
        } else {
            $message = 'Failed to update student.';
            $messageType = 'error';
        }
        $stmt->close();
    } else {
        $message = implode(' ', $errors);
        $messageType = 'error';
    }
}

// -------------------------
// Handle CSV EXPORT
// -------------------------
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="students_export.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Full Name', 'Email', 'Phone', 'Course', 'Year', 'Gender', 'Address', 'Created At']);

    $result = $conn->query("SELECT id, fullname, email, phone, course, year, gender, address, created_at FROM students WHERE status = 'active' ORDER BY id");
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }

    fclose($output);
    $conn->close();
    exit;
}

// -------------------------
// Fetch students with search/filter
// -------------------------
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filterCourse = isset($_GET['course_filter']) ? trim($_GET['course_filter']) : '';

$sql = "SELECT * FROM students WHERE status = 'active'";
$params = [];
$types = '';

if (!empty($search)) {
    $sql .= " AND (fullname LIKE ? OR email LIKE ? OR course LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'sss';
}

if (!empty($filterCourse)) {
    $sql .= " AND course = ?";
    $params[] = $filterCourse;
    $types .= 's';
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

// -------------------------
// Get student for editing
// -------------------------
$editStudent = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM students WHERE id = ? AND status = 'active'");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $editResult = $stmt->get_result();
    if ($editResult->num_rows === 1) {
        $editStudent = $editResult->fetch_assoc();
    }
    $stmt->close();
}

// Get distinct courses for filter dropdown
$coursesResult = $conn->query("SELECT DISTINCT course FROM students WHERE status = 'active' ORDER BY course");
$courses = [];
while ($row = $coursesResult->fetch_assoc()) {
    $courses[] = $row['course'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Student Management</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .success-msg {
            color: green; font-weight: bold; background-color: #d4edda;
            border: 2px solid green; padding: 12px; border-radius: 8px;
            margin: 10px auto; max-width: 700px;
        }
        .error-msg {
            color: red; font-weight: bold; background-color: #f8d7da;
            border: 2px solid red; padding: 12px; border-radius: 8px;
            margin: 10px auto; max-width: 700px;
        }
        table.records {
            border-collapse: collapse; margin: 10px auto; max-width: 95%;
        }
        table.records th, table.records td {
            border: 2px solid rgb(17, 8, 8); padding: 6px 10px; text-align: left; font-size: 14px;
        }
        table.records th {
            background-color: rgb(144, 164, 165); color: white;
        }
        table.records tr:nth-child(even) { background-color: #eaf4fc; }
        .search-bar { margin: 10px auto; }
        .search-bar input, .search-bar select { padding: 6px 8px; margin: 4px; }
        .action-btn {
            padding: 4px 10px; margin: 2px; border-radius: 4px; cursor: pointer; font-size: 13px;
        }
        .edit-btn { background-color: #ffc107; border: 1px solid #e0a800; }
        .delete-btn { background-color: #dc3545; color: white; border: 1px solid #c82333; }
    </style>
</head>
<body>

    <div id="header">
        <h1 align="center">STUDENTHUB</h1>
    </div>

    <br>

    <div align="center">
        <a href="home.HTML"><button type="button">Home</button></a>
        <?php if ($_SESSION['role'] === 'admin'): ?>
            <a href="practical14_dashboard.php"><button type="button">Admin Dashboard</button></a>
        <?php else: ?>
            <a href="practical10_student_dashboard.php"><button type="button">Dashboard</button></a>
        <?php endif; ?>
        <a href="practical10_logout.php"><button type="button">Logout</button></a>
    </div>

    <br>
    <hr>
    <br>

    <div align="center">

        <h2><u>Student Management (Practical 11)</u></h2>

        <?php if (!empty($message)): ?>
            <div class="<?php echo $messageType === 'success' ? 'success-msg' : 'error-msg'; ?>">
                <p><?php echo htmlspecialchars($message); ?></p>
            </div>
        <?php endif; ?>

        <br>

        <!-- Add / Edit Form -->
        <form action="practical11_students.php" method="POST" class="card">

            <h3><?php echo $editStudent ? 'Edit Student' : 'Add New Student'; ?></h3>

            <?php if ($editStudent): ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="edit_id" value="<?php echo $editStudent['id']; ?>">
            <?php else: ?>
                <input type="hidden" name="action" value="add">
            <?php endif; ?>

            <table class="form-table" align="center">
                <tr>
                    <td><label>Full Name:</label></td>
                    <td><input type="text" name="fullname" placeholder="Enter name" value="<?php echo $editStudent ? htmlspecialchars($editStudent['fullname']) : ''; ?>" required></td>
                </tr>
                <tr>
                    <td><label>Email:</label></td>
                    <td><input type="email" name="email" placeholder="abc@gmail.com" value="<?php echo $editStudent ? htmlspecialchars($editStudent['email']) : ''; ?>" required></td>
                </tr>
                <tr>
                    <td><label>Phone:</label></td>
                    <td><input type="tel" name="phone" placeholder="10-digit number" value="<?php echo $editStudent ? htmlspecialchars($editStudent['phone']) : ''; ?>" required></td>
                </tr>
                <tr>
                    <td><label>Course:</label></td>
                    <td>
                        <select name="course" required>
                            <option value="">Select Course</option>
                            <?php
                            $courseOptions = ['CE', 'IT', 'EC', 'ME'];
                            foreach ($courseOptions as $c) {
                                $selected = ($editStudent && $editStudent['course'] === $c) ? 'selected' : '';
                                echo "<option value=\"$c\" $selected>$c</option>";
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td><label>Year:</label></td>
                    <td>
                        <select name="year" required>
                            <option value="">Select Year</option>
                            <?php for ($y = 1; $y <= 4; $y++): ?>
                                <option value="<?php echo $y; ?>" <?php echo ($editStudent && $editStudent['year'] == $y) ? 'selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td><label>Gender:</label></td>
                    <td>
                        <select name="gender" required>
                            <option value="">Select Gender</option>
                            <?php
                            $genders = ['Male', 'Female', 'Other'];
                            foreach ($genders as $g) {
                                $selected = ($editStudent && $editStudent['gender'] === $g) ? 'selected' : '';
                                echo "<option value=\"$g\" $selected>$g</option>";
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td><label>Address:</label></td>
                    <td><input type="text" name="address" placeholder="City, State" value="<?php echo $editStudent ? htmlspecialchars($editStudent['address']) : ''; ?>"></td>
                </tr>
            </table>

            <br>
            <button type="submit"><?php echo $editStudent ? 'Update Student' : 'Add Student'; ?></button>
            <?php if ($editStudent): ?>
                <a href="practical11_students.php"><button type="button">Cancel</button></a>
            <?php endif; ?>
        </form>

        <br>

        <!-- Search and Filter -->
        <div class="search-bar">
            <form action="practical11_students.php" method="GET">
                <input type="text" name="search" placeholder="Search name/email/course" value="<?php echo htmlspecialchars($search); ?>">
                <select name="course_filter">
                    <option value="">All Courses</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?php echo htmlspecialchars($c); ?>" <?php echo ($filterCourse === $c) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Search</button>
                <a href="practical11_students.php"><button type="button">Clear</button></a>
                <a href="practical11_students.php?export=csv"><button type="button">Export CSV</button></a>
            </form>
        </div>

        <br>

        <!-- Student Records Table -->
        <p><strong>Total Records: <?php echo count($students); ?></strong></p>

        <?php if (count($students) > 0): ?>
            <table class="records">
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Course</th>
                    <th>Year</th>
                    <th>Gender</th>
                    <th>Address</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($students as $s): ?>
                    <tr>
                        <td><?php echo $s['id']; ?></td>
                        <td><?php echo htmlspecialchars($s['fullname']); ?></td>
                        <td><?php echo htmlspecialchars($s['email']); ?></td>
                        <td><?php echo htmlspecialchars($s['phone']); ?></td>
                        <td><?php echo htmlspecialchars($s['course']); ?></td>
                        <td><?php echo $s['year']; ?></td>
                        <td><?php echo htmlspecialchars($s['gender']); ?></td>
                        <td><?php echo htmlspecialchars($s['address']); ?></td>
                        <td>
                            <a href="practical11_students.php?edit=<?php echo $s['id']; ?>"><button class="action-btn edit-btn">Edit</button></a>
                            <a href="practical11_students.php?delete=<?php echo $s['id']; ?>" onclick="return confirm('Are you sure you want to delete this student?');"><button class="action-btn delete-btn">Delete</button></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>No student records found.</p>
        <?php endif; ?>

        <br><br>

        <a href="home.HTML">
            <button type="button">BACK</button>
        </a>

    </div>

    <br>
    <hr>

    <footer align="center">
        <h4>@ 2026 Jaimin Khambhaliya</h4>
    </footer>

</body>
</html>
<?php $conn->close(); ?>
