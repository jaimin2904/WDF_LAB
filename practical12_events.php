<?php
/**
 * Practical 12: Event Management CRUD with Poster Upload and File Validation
 *
 * Features:
 * - Add, View, Update, Delete events
 * - Poster image upload with file type and size validation
 * - Success/failure messages
 * - MySQLi prepared statements
 * - Event category and open/closed filter
 */

require_once 'auth_check.php';
require_once 'db.php';

// Only admins can manage events
if ($_SESSION['role'] !== 'admin') {
    echo '<div style="text-align:center;padding:50px;"><h2>Access Denied</h2><p>Only admin users can manage events.</p><a href="practical10_login.php"><button>Go to Login</button></a></div>';
    exit;
}

// Create uploads directory if not exists
$uploadDir = __DIR__ . '/uploads/posters/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$message = '';
$messageType = '';

// -------------------------
// Handle DELETE
// -------------------------
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];

    // Get poster filename to delete file
    $stmt = $conn->prepare("SELECT poster FROM events WHERE id = ?");
    $stmt->bind_param("i", $deleteId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $event = $result->fetch_assoc();
        if (!empty($event['poster']) && file_exists($uploadDir . $event['poster'])) {
            unlink($uploadDir . $event['poster']);
        }
    }
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
    $stmt->bind_param("i", $deleteId);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $message = 'Event deleted successfully.';
        $messageType = 'success';

        // Audit log
        $action = 'DELETE_EVENT';
        $details = "Deleted event ID: $deleteId";
        $ip = $_SERVER['REMOTE_ADDR'];
        $logStmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $logStmt->bind_param("isss", $_SESSION['user_id'], $action, $details, $ip);
        $logStmt->execute();
        $logStmt->close();
    } else {
        $message = 'Failed to delete event.';
        $messageType = 'error';
    }
    $stmt->close();
}

// -------------------------
// Handle poster upload helper
// -------------------------
function handlePosterUpload($uploadDir) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 2 * 1024 * 1024; // 2MB

    if (!isset($_FILES['poster']) || $_FILES['poster']['error'] === UPLOAD_ERR_NO_FILE) {
        return ['filename' => '', 'error' => ''];
    }

    $file = $_FILES['poster'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['filename' => '', 'error' => 'File upload error.'];
    }

    // Validate file type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, $allowedTypes)) {
        return ['filename' => '', 'error' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.'];
    }

    // Validate file size
    if ($file['size'] > $maxSize) {
        return ['filename' => '', 'error' => 'File size exceeds 2MB limit.'];
    }

    // Generate unique filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'poster_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;

    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        return ['filename' => $filename, 'error' => ''];
    } else {
        return ['filename' => '', 'error' => 'Failed to move uploaded file.'];
    }
}

// -------------------------
// Handle ADD
// -------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $event_date  = trim($_POST['event_date']);
    $location    = trim($_POST['location']);
    $category    = trim($_POST['category']);
    $event_status = trim($_POST['event_status']);

    $errors = [];

    if (empty($title)) $errors[] = 'Title is required.';
    if (empty($event_date)) $errors[] = 'Event date is required.';
    if (empty($location)) $errors[] = 'Location is required.';
    if (empty($category)) $errors[] = 'Category is required.';
    if (!in_array($event_status, ['open', 'closed'])) $errors[] = 'Valid status is required.';

    // Handle poster upload
    $posterResult = handlePosterUpload($uploadDir);
    if (!empty($posterResult['error'])) {
        $errors[] = $posterResult['error'];
    }

    if (empty($errors)) {
        $poster = $posterResult['filename'];
        $createdBy = $_SESSION['user_id'];

        $stmt = $conn->prepare("INSERT INTO events (title, description, event_date, location, category, poster, event_status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssi", $title, $description, $event_date, $location, $category, $poster, $event_status, $createdBy);

        if ($stmt->execute()) {
            $message = 'Event added successfully!';
            $messageType = 'success';

            // Audit log
            $action = 'CREATE_EVENT';
            $details = "Created event: $title";
            $ip = $_SERVER['REMOTE_ADDR'];
            $logStmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
            $logStmt->bind_param("isss", $_SESSION['user_id'], $action, $details, $ip);
            $logStmt->execute();
            $logStmt->close();
        } else {
            $message = 'Failed to add event.';
            $messageType = 'error';
        }
        $stmt->close();
    } else {
        $message = implode(' ', $errors);
        $messageType = 'error';
    }
}

// -------------------------
// Handle EDIT
// -------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $editId      = (int)$_POST['edit_id'];
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $event_date  = trim($_POST['event_date']);
    $location    = trim($_POST['location']);
    $category    = trim($_POST['category']);
    $event_status = trim($_POST['event_status']);

    $errors = [];

    if (empty($title)) $errors[] = 'Title is required.';
    if (empty($event_date)) $errors[] = 'Event date is required.';
    if (empty($location)) $errors[] = 'Location is required.';
    if (empty($category)) $errors[] = 'Category is required.';
    if (!in_array($event_status, ['open', 'closed'])) $errors[] = 'Valid status is required.';

    // Handle poster upload
    $posterResult = handlePosterUpload($uploadDir);
    if (!empty($posterResult['error'])) {
        $errors[] = $posterResult['error'];
    }

    if (empty($errors)) {
        if (!empty($posterResult['filename'])) {
            // Delete old poster
            $stmt = $conn->prepare("SELECT poster FROM events WHERE id = ?");
            $stmt->bind_param("i", $editId);
            $stmt->execute();
            $oldResult = $stmt->get_result();
            if ($oldResult->num_rows > 0) {
                $oldEvent = $oldResult->fetch_assoc();
                if (!empty($oldEvent['poster']) && file_exists($uploadDir . $oldEvent['poster'])) {
                    unlink($uploadDir . $oldEvent['poster']);
                }
            }
            $stmt->close();

            $poster = $posterResult['filename'];
            $stmt = $conn->prepare("UPDATE events SET title=?, description=?, event_date=?, location=?, category=?, poster=?, event_status=? WHERE id=?");
            $stmt->bind_param("sssssssi", $title, $description, $event_date, $location, $category, $poster, $event_status, $editId);
        } else {
            $stmt = $conn->prepare("UPDATE events SET title=?, description=?, event_date=?, location=?, category=?, event_status=? WHERE id=?");
            $stmt->bind_param("ssssssi", $title, $description, $event_date, $location, $category, $event_status, $editId);
        }

        if ($stmt->execute()) {
            $message = 'Event updated successfully!';
            $messageType = 'success';

            // Audit log
            $action = 'UPDATE_EVENT';
            $details = "Updated event ID $editId: $title";
            $ip = $_SERVER['REMOTE_ADDR'];
            $logStmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
            $logStmt->bind_param("isss", $_SESSION['user_id'], $action, $details, $ip);
            $logStmt->execute();
            $logStmt->close();
        } else {
            $message = 'Failed to update event.';
            $messageType = 'error';
        }
        $stmt->close();
    } else {
        $message = implode(' ', $errors);
        $messageType = 'error';
    }
}

// -------------------------
// Fetch events with filters
// -------------------------
$filterCategory = isset($_GET['category_filter']) ? trim($_GET['category_filter']) : '';
$filterStatus = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : '';

$sql = "SELECT * FROM events WHERE 1=1";
$params = [];
$types = '';

if (!empty($filterCategory)) {
    $sql .= " AND category = ?";
    $params[] = $filterCategory;
    $types .= 's';
}

if (!empty($filterStatus)) {
    $sql .= " AND event_status = ?";
    $params[] = $filterStatus;
    $types .= 's';
}

$sql .= " ORDER BY event_date DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$events = [];
while ($row = $result->fetch_assoc()) {
    $events[] = $row;
}
$stmt->close();

// Get edit event data
$editEvent = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $editResult = $stmt->get_result();
    if ($editResult->num_rows === 1) {
        $editEvent = $editResult->fetch_assoc();
    }
    $stmt->close();
}

// Get distinct categories
$catResult = $conn->query("SELECT DISTINCT category FROM events ORDER BY category");
$categories = [];
while ($row = $catResult->fetch_assoc()) {
    $categories[] = $row['category'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Event Management</title>
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
        .action-btn {
            padding: 4px 10px; margin: 2px; border-radius: 4px; cursor: pointer; font-size: 13px;
        }
        .edit-btn { background-color: #ffc107; border: 1px solid #e0a800; }
        .delete-btn { background-color: #dc3545; color: white; border: 1px solid #c82333; }
        .poster-thumb { max-width: 80px; max-height: 60px; border-radius: 4px; }
        .status-open { color: green; font-weight: bold; }
        .status-closed { color: red; font-weight: bold; }
    </style>
</head>
<body>

    <div id="header">
        <h1 align="center">STUDENTHUB</h1>
    </div>

    <br>

    <div align="center">
        <a href="home.HTML"><button type="button">Home</button></a>
        <a href="practical14_dashboard.php"><button type="button">Admin Dashboard</button></a>
        <a href="practical10_logout.php"><button type="button">Logout</button></a>
    </div>

    <br>
    <hr>
    <br>

    <div align="center">

        <h2><u>Event Management (Practical 12)</u></h2>

        <?php if (!empty($message)): ?>
            <div class="<?php echo $messageType === 'success' ? 'success-msg' : 'error-msg'; ?>">
                <p><?php echo htmlspecialchars($message); ?></p>
            </div>
        <?php endif; ?>

        <br>

        <!-- Add / Edit Form -->
        <form action="practical12_events.php" method="POST" enctype="multipart/form-data" class="card">

            <h3><?php echo $editEvent ? 'Edit Event' : 'Add New Event'; ?></h3>

            <?php if ($editEvent): ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="edit_id" value="<?php echo $editEvent['id']; ?>">
            <?php else: ?>
                <input type="hidden" name="action" value="add">
            <?php endif; ?>

            <table class="form-table" align="center">
                <tr>
                    <td><label>Title:</label></td>
                    <td><input type="text" name="title" placeholder="Event title" value="<?php echo $editEvent ? htmlspecialchars($editEvent['title']) : ''; ?>" required></td>
                </tr>
                <tr>
                    <td><label>Description:</label></td>
                    <td><textarea name="description" rows="3" cols="35" placeholder="Event description"><?php echo $editEvent ? htmlspecialchars($editEvent['description']) : ''; ?></textarea></td>
                </tr>
                <tr>
                    <td><label>Date:</label></td>
                    <td><input type="date" name="event_date" value="<?php echo $editEvent ? $editEvent['event_date'] : ''; ?>" required></td>
                </tr>
                <tr>
                    <td><label>Location:</label></td>
                    <td><input type="text" name="location" placeholder="Event venue" value="<?php echo $editEvent ? htmlspecialchars($editEvent['location']) : ''; ?>" required></td>
                </tr>
                <tr>
                    <td><label>Category:</label></td>
                    <td>
                        <select name="category" required>
                            <option value="">Select Category</option>
                            <?php
                            $catOptions = ['Technical', 'Sports', 'Cultural', 'Workshop', 'Academic', 'Career'];
                            foreach ($catOptions as $c) {
                                $selected = ($editEvent && $editEvent['category'] === $c) ? 'selected' : '';
                                echo "<option value=\"$c\" $selected>$c</option>";
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td><label>Status:</label></td>
                    <td>
                        <select name="event_status" required>
                            <option value="open" <?php echo ($editEvent && $editEvent['event_status'] === 'open') ? 'selected' : ''; ?>>Open</option>
                            <option value="closed" <?php echo ($editEvent && $editEvent['event_status'] === 'closed') ? 'selected' : ''; ?>>Closed</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td><label>Poster:</label></td>
                    <td>
                        <input type="file" name="poster" accept="image/jpeg,image/png,image/gif,image/webp">
                        <br><small>Max 2MB. JPEG, PNG, GIF, WebP only.</small>
                        <?php if ($editEvent && !empty($editEvent['poster'])): ?>
                            <br><small>Current: <?php echo htmlspecialchars($editEvent['poster']); ?></small>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <br>
            <button type="submit"><?php echo $editEvent ? 'Update Event' : 'Add Event'; ?></button>
            <?php if ($editEvent): ?>
                <a href="practical12_events.php"><button type="button">Cancel</button></a>
            <?php endif; ?>
        </form>

        <br>

        <!-- Filters -->
        <form action="practical12_events.php" method="GET">
            <select name="category_filter">
                <option value="">All Categories</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?php echo htmlspecialchars($c); ?>" <?php echo ($filterCategory === $c) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status_filter">
                <option value="">All Status</option>
                <option value="open" <?php echo ($filterStatus === 'open') ? 'selected' : ''; ?>>Open</option>
                <option value="closed" <?php echo ($filterStatus === 'closed') ? 'selected' : ''; ?>>Closed</option>
            </select>
            <button type="submit">Filter</button>
            <a href="practical12_events.php"><button type="button">Clear</button></a>
        </form>

        <br>

        <!-- Events Table -->
        <p><strong>Total Events: <?php echo count($events); ?></strong></p>

        <?php if (count($events) > 0): ?>
            <table class="records">
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Date</th>
                    <th>Location</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Poster</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($events as $e): ?>
                    <tr>
                        <td><?php echo $e['id']; ?></td>
                        <td><?php echo htmlspecialchars($e['title']); ?></td>
                        <td><?php echo $e['event_date']; ?></td>
                        <td><?php echo htmlspecialchars($e['location']); ?></td>
                        <td><?php echo htmlspecialchars($e['category']); ?></td>
                        <td class="<?php echo $e['event_status'] === 'open' ? 'status-open' : 'status-closed'; ?>">
                            <?php echo ucfirst($e['event_status']); ?>
                        </td>
                        <td>
                            <?php if (!empty($e['poster']) && file_exists($uploadDir . $e['poster'])): ?>
                                <img src="uploads/posters/<?php echo htmlspecialchars($e['poster']); ?>" class="poster-thumb" alt="Poster">
                            <?php else: ?>
                                No poster
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="practical12_events.php?edit=<?php echo $e['id']; ?>"><button class="action-btn edit-btn">Edit</button></a>
                            <a href="practical12_events.php?delete=<?php echo $e['id']; ?>" onclick="return confirm('Are you sure you want to delete this event?');"><button class="action-btn delete-btn">Delete</button></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>No events found.</p>
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
