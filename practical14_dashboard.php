<?php
/**
 * Practical 14: Admin Dashboard with Role-Based Access Control (RBAC),
 * Dynamic Menu, User Statistics, Latest Registrations, and Audit Logs
 * 
 * Features:
 * - Strict Role-Based Access Control (RBAC): Admin only
 * - Dynamic Role-Based Menu
 * - Dynamic SQL aggregation metrics (Users, Students, Events, Registrations)
 * - Recent event registrations table
 * - User status toggle (Active / Inactive) - Intermediate Extension
 * - Visual analytics breakdown chart - Advanced Extension
 * - Real-time audit logs viewer
 */

require_once 'auth_check.php';
require_once 'db.php';

// Strict Role-Based Access Control: Admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Access Denied - Admin Only</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body style="font-family:Arial, sans-serif; text-align:center; padding:60px 20px; background:#f7f9fa;">
        <div style="max-width:500px; margin:0 auto; background:white; padding:30px; border-radius:8px; border:2px solid #dc3545; box-shadow:0 4px 12px rgba(0,0,0,0.1);">
            <h2 style="color:#dc3545; margin-top:0;">⛔ Access Denied</h2>
            <p>You do not have administrative privileges to view this page.</p>
            <p style="color:#666; font-size:14px;">Logged in as: <strong>' . htmlspecialchars($_SESSION['username'] ?? 'User') . '</strong> (Role: ' . htmlspecialchars($_SESSION['role'] ?? 'None') . ')</p>
            <br>
            <a href="practical10_student_dashboard.php"><button type="button">Go to Student Dashboard</button></a>
            <a href="practical10_logout.php"><button type="button" style="color:red;">Logout</button></a>
        </div>
    </body>
    </html>';
    exit;
}

$message = '';
$messageType = '';

// -------------------------------------------------------------
// Intermediate Extension: Toggle User Status (Active / Inactive)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_user_status') {
    $targetUserId = (int)($_POST['user_id'] ?? 0);

    // Prevent admin from deactivating themselves
    if ($targetUserId === (int)$_SESSION['user_id']) {
        $message = 'You cannot change your own account status.';
        $messageType = 'error';
    } elseif ($targetUserId > 0) {
        // Fetch current status
        $statusStmt = $conn->prepare("SELECT username, email, status FROM users WHERE id = ?");
        $statusStmt->bind_param("i", $targetUserId);
        $statusStmt->execute();
        $userRes = $statusStmt->get_result();

        if ($userRes->num_rows === 1) {
            $uData = $userRes->fetch_assoc();
            $newStatus = ($uData['status'] === 'active') ? 'inactive' : 'active';
            $statusStmt->close();

            $updateStmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
            $updateStmt->bind_param("si", $newStatus, $targetUserId);
            if ($updateStmt->execute()) {
                $message = "User '{$uData['username']}' status changed to " . strtoupper($newStatus) . ".";
                $messageType = 'success';

                // Audit log
                $action = 'TOGGLE_USER_STATUS';
                $details = "Admin changed status of {$uData['username']} (ID: $targetUserId) to $newStatus";
                $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                $logStmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
                $logStmt->bind_param("isss", $_SESSION['user_id'], $action, $details, $ip);
                $logStmt->execute();
                $logStmt->close();
            } else {
                $message = 'Failed to update user status.';
                $messageType = 'error';
            }
            $updateStmt->close();
        } else {
            $statusStmt->close();
            $message = 'User not found.';
            $messageType = 'error';
        }
    }
}

// -------------------------------------------------------------
// Dynamic Aggregations & Database Metrics
// -------------------------------------------------------------
// 1. Total Users
$userCountRes = $conn->query("SELECT COUNT(*) AS total, 
                                SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) AS admin_count,
                                SUM(CASE WHEN role = 'student' THEN 1 ELSE 0 END) AS student_user_count,
                                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_count
                              FROM users");
$userData = $userCountRes->fetch_assoc();

// 2. Total Students
$studentCountRes = $conn->query("SELECT COUNT(*) AS total FROM students WHERE status != 'deleted'");
$totalStudents = (int)$studentCountRes->fetch_assoc()['total'];

// 3. Total Events & Open Events
$eventCountRes = $conn->query("SELECT COUNT(*) AS total, 
                                 SUM(CASE WHEN event_status = 'open' THEN 1 ELSE 0 END) AS open_count 
                               FROM events");
$eventData = $eventCountRes->fetch_assoc();

// 4. Total Registrations
$regCountRes = $conn->query("SELECT COUNT(*) AS total FROM registrations");
$totalRegistrations = (int)$regCountRes->fetch_assoc()['total'];

// 5. Latest 8 Registrations with Join
$latestRegQuery = "SELECT r.id, r.registered_at, 
                          s.fullname AS student_name, s.email AS student_email, s.course,
                          e.title AS event_title, e.event_date, e.category
                   FROM registrations r
                   JOIN students s ON r.student_id = s.id
                   JOIN events e ON r.event_id = e.id
                   ORDER BY r.id DESC LIMIT 8";
$latestRegs = $conn->query($latestRegQuery);

// 6. Registered Users List (for Intermediate status toggle)
$usersQuery = "SELECT id, username, email, role, status, last_login, created_at 
               FROM users 
               ORDER BY id DESC LIMIT 10";
$recentUsers = $conn->query($usersQuery);

// 7. Audit Logs (Recent 10 activities)
$auditQuery = "SELECT a.id, a.action, a.details, a.ip_address, a.created_at, u.username
               FROM audit_logs a
               LEFT JOIN users u ON a.user_id = u.id
               ORDER BY a.id DESC LIMIT 10";
$auditLogs = $conn->query($auditQuery);

// 8. Registrations per Event (for Advanced Extension Analytics Chart)
$chartQuery = "SELECT e.title, COUNT(r.id) AS reg_count 
               FROM events e 
               LEFT JOIN registrations r ON e.id = r.event_id 
               GROUP BY e.id, e.title 
               ORDER BY reg_count DESC LIMIT 6";
$chartData = $conn->query($chartQuery);
$chartLabels = [];
$chartValues = [];
while ($row = $chartData->fetch_assoc()) {
    $chartLabels[] = $row['title'];
    $chartValues[] = (int)$row['reg_count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Admin Dashboard (Practical 14)</title>
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --primary: #1f4e5b;
            --primary-dark: #14353e;
            --accent: #e76f51;
            --success: #2a9d8f;
            --warning: #e9c46a;
            --danger: #e63946;
            --bg-light: #f4f6f9;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: var(--bg-light);
            margin: 0;
            padding: 10px;
        }

        .dashboard-container {
            max-width: 1250px;
            margin: 0 auto;
        }

        /* Dynamic Menu Styles */
        .dynamic-navbar {
            background: #1f4e5b;
            padding: 10px 20px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.12);
        }

        .nav-role-badge {
            background: #e76f51;
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .nav-links {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .nav-links a button {
            background: white;
            color: #1f4e5b;
            border: 1px solid #1f4e5b;
            font-weight: bold;
            padding: 7px 12px;
            border-radius: 6px;
            cursor: pointer;
            box-shadow: none;
            transition: all 0.2s;
        }

        .nav-links a button:hover {
            background: #eaf4fc;
        }

        .nav-links a.active button {
            background: #e9c46a;
            color: #333;
            border-color: #d4a737;
        }

        /* Metric Cards Grid */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
            margin-bottom: 25px;
        }

        .metric-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border-top: 4px solid var(--primary);
            position: relative;
            overflow: hidden;
        }
        .metric-card.users { border-top-color: #3a86ff; }
        .metric-card.students { border-top-color: #2a9d8f; }
        .metric-card.events { border-top-color: #e76f51; }
        .metric-card.regs { border-top-color: #f4a261; }

        .metric-number {
            font-size: 32px;
            font-weight: bold;
            color: #222;
            margin: 5px 0;
        }

        .metric-label {
            font-size: 13px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .metric-sub {
            font-size: 12px;
            color: #888;
            margin-top: 6px;
        }

        /* Grid for Panels */
        .panels-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        @media (max-width: 900px) {
            .panels-grid { grid-template-columns: 1fr; }
        }

        .panel {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin-bottom: 20px;
        }

        .panel-title {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 18px;
            color: #1f4e5b;
            border-bottom: 2px solid #eef2f5;
            padding-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Table Styles */
        .dash-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .dash-table th {
            background-color: #f1f5f8;
            color: #333;
            text-align: left;
            padding: 10px;
            border-bottom: 2px solid #dee2e6;
        }

        .dash-table td {
            padding: 10px;
            border-bottom: 1px solid #edf2f7;
            vertical-align: middle;
        }

        .dash-table tr:hover {
            background-color: #f8fafc;
        }

        /* Badges */
        .tag {
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: bold;
            display: inline-block;
        }
        .tag-active { background: #d4edda; color: #155724; }
        .tag-inactive { background: #f8d7da; color: #721c24; }
        .tag-admin { background: #cce5ff; color: #004085; }
        .tag-student { background: #e2e3e5; color: #383d41; }
        .tag-tech { background: #d1ecf1; color: #0c5460; }
        .tag-cult { background: #fce8e6; color: #c5221f; }
        .tag-sports { background: #e6f4ea; color: #137333; }

        /* Status toggle button */
        .btn-toggle {
            padding: 4px 8px;
            font-size: 11px;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            background: white;
            font-weight: bold;
        }
        .btn-toggle:hover {
            background: #eee;
        }

        /* Simple Visual Bar Chart (Advanced Extension) */
        .chart-box {
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding-top: 10px;
        }
        .chart-row {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .chart-row-header {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            font-weight: bold;
            color: #444;
        }
        .chart-bar-bg {
            background: #e9ecef;
            border-radius: 6px;
            height: 18px;
            overflow: hidden;
        }
        .chart-bar-fill {
            background: linear-gradient(90deg, #1f4e5b, #2a9d8f);
            height: 100%;
            border-radius: 6px;
            transition: width 0.6s ease;
        }

        /* Alert message */
        .alert {
            padding: 12px 18px;
            border-radius: 6px;
            margin-bottom: 18px;
            font-size: 14px;
            font-weight: 500;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>

    <div id="header">
        <h1 align="center">STUDENTHUB</h1>
    </div>

    <br>

    <div class="dashboard-container">

        <!-- DYNAMIC MENU BASED ON ROLE (Admin vs Student) -->
        <div class="dynamic-navbar">
            <div style="display:flex; align-items:center; gap:12px;">
                <span style="color:white; font-size:16px; font-weight:bold;">StudentHub Portal</span>
                <span class="nav-role-badge">Role: <?php echo htmlspecialchars($_SESSION['role']); ?></span>
            </div>
            
            <div class="nav-links">
                <a href="home.HTML"><button type="button">Home</button></a>
                <a href="practical14_dashboard.php" class="active"><button type="button">Dashboard (P14)</button></a>
                <a href="practical11_students.php"><button type="button">Students (P11)</button></a>
                <a href="practical12_events.php"><button type="button">Events (P12)</button></a>
                <a href="practical13_ajax.php"><button type="button">AJAX API (P13)</button></a>
                <a href="practical10_logout.php"><button type="button" style="color:red; background:#ffebeb; border-color:#f5c2c7;">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</button></a>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Welcome Banner -->
        <div style="background:white; padding:16px 20px; border-radius:8px; margin-bottom:20px; box-shadow:0 2px 8px rgba(0,0,0,0.05); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
            <div>
                <h2 style="margin:0; color:#1f4e5b;">Welcome to the Administrative Control Center</h2>
                <p style="margin:5px 0 0; color:#666; font-size:14px;">
                    Signed in as <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong> (<?php echo htmlspecialchars($_SESSION['email']); ?>)
                </p>
            </div>
            <div>
                <span style="background:#eaf4fc; color:#1f4e5b; border:1px solid #b8daff; padding:6px 12px; border-radius:6px; font-size:13px; font-weight:bold;">
                    Server Time: <?php echo date('d M Y, h:i A'); ?>
                </span>
            </div>
        </div>

        <!-- 1. KEY METRICS CARDS (Dynamic aggregations) -->
        <div class="metrics-grid">
            <div class="metric-card users">
                <div class="metric-label">Total Users</div>
                <div class="metric-number"><?php echo (int)($userData['total'] ?? 0); ?></div>
                <div class="metric-sub">
                    <strong><?php echo (int)($userData['active_count'] ?? 0); ?></strong> Active &bull; 
                    <strong><?php echo (int)($userData['admin_count'] ?? 0); ?></strong> Admins
                </div>
            </div>

            <div class="metric-card students">
                <div class="metric-label">Enrolled Students</div>
                <div class="metric-number"><?php echo $totalStudents; ?></div>
                <div class="metric-sub">
                    In database across CE, IT, EC, ME
                </div>
            </div>

            <div class="metric-card events">
                <div class="metric-label">Total Events</div>
                <div class="metric-number"><?php echo (int)($eventData['total'] ?? 0); ?></div>
                <div class="metric-sub">
                    <strong><?php echo (int)($eventData['open_count'] ?? 0); ?></strong> Currently Open
                </div>
            </div>

            <div class="metric-card regs">
                <div class="metric-label">Event Registrations</div>
                <div class="metric-number"><?php echo $totalRegistrations; ?></div>
                <div class="metric-sub">
                    Participating student signups
                </div>
            </div>
        </div>

        <!-- 2. PANELS GRID (Left: Latest Registrations, Right: Analytics Chart) -->
        <div class="panels-grid">

            <!-- Latest Event Registrations -->
            <div class="panel">
                <div class="panel-title">
                    <span>📋 Latest Event Registrations</span>
                    <span style="font-size:12px; font-weight:normal; color:#888;">Real-time database join</span>
                </div>
                <div style="overflow-x:auto;">
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Department</th>
                                <th>Event Title</th>
                                <th>Category</th>
                                <th>Registration Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($latestRegs && $latestRegs->num_rows > 0): ?>
                                <?php while ($reg = $latestRegs->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($reg['student_name']); ?></strong><br>
                                            <span style="font-size:11px; color:#777;"><?php echo htmlspecialchars($reg['student_email']); ?></span>
                                        </td>
                                        <td><span class="tag tag-student"><?php echo htmlspecialchars($reg['course']); ?></span></td>
                                        <td><strong><?php echo htmlspecialchars($reg['event_title']); ?></strong></td>
                                        <td>
                                            <?php 
                                                $cat = strtolower($reg['category']);
                                                $catTag = ($cat === 'technical') ? 'tag-tech' : (($cat === 'sports') ? 'tag-sports' : 'tag-cult');
                                            ?>
                                            <span class="tag <?php echo $catTag; ?>"><?php echo htmlspecialchars($reg['category']); ?></span>
                                        </td>
                                        <td style="font-size:12px; color:#555;">
                                            <?php echo date('d M Y, h:i A', strtotime($reg['registered_at'])); ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align:center; padding:20px; color:#888;">No event registrations found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Advanced Extension: Visual Analytics Bar Chart -->
            <div class="panel">
                <div class="panel-title">
                    <span>📊 Registrations by Event</span>
                    <span class="tag tag-tech" style="font-size:10px;">Analytics</span>
                </div>
                <p style="font-size:12px; color:#666; margin-top:-5px;">Top events by student participation:</p>
                <div class="chart-box">
                    <?php 
                        $maxVal = max(array_merge([1], $chartValues));
                        for ($i = 0; $i < count($chartLabels); $i++): 
                            $pct = round(($chartValues[$i] / $maxVal) * 100);
                    ?>
                        <div class="chart-row">
                            <div class="chart-row-header">
                                <span title="<?php echo htmlspecialchars($chartLabels[$i]); ?>">
                                    <?php echo htmlspecialchars(mb_strimwidth($chartLabels[$i], 0, 22, '...')); ?>
                                </span>
                                <span><?php echo $chartValues[$i]; ?> regs</span>
                            </div>
                            <div class="chart-bar-bg">
                                <div class="chart-bar-fill" style="width: <?php echo max(5, $pct); ?>%;"></div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>

                <hr style="margin:20px 0; border:0; border-top:1px solid #eee;">
                
                <h4 style="margin:0 0 10px; font-size:14px; color:#1f4e5b;">Quick Admin Actions</h4>
                <div style="display:flex; flex-direction:column; gap:8px;">
                    <a href="practical11_students.php?export=csv"><button type="button" style="width:100%; text-align:left; background:#eaf4fc;">📥 Export Students to CSV</button></a>
                    <a href="practical12_events.php"><button type="button" style="width:100%; text-align:left; background:#eaf4fc;">➕ Create New Event</button></a>
                    <a href="practical13_ajax.php"><button type="button" style="width:100%; text-align:left; background:#eaf4fc;">⚡ Launch AJAX Fetch CRUD</button></a>
                </div>
            </div>

        </div>

        <!-- 3. INTERMEDIATE EXTENSION: USER MANAGEMENT & STATUS TOGGLE -->
        <div class="panel">
            <div class="panel-title">
                <span>👥 User Management & Status Toggle (Intermediate Extension)</span>
                <span style="font-size:12px; font-weight:normal; color:#888;">Toggle Active / Inactive status instantly</span>
            </div>
            <div style="overflow-x:auto;">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Account Status</th>
                            <th>Last Login</th>
                            <th>Created On</th>
                            <th style="text-align:center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recentUsers && $recentUsers->num_rows > 0): ?>
                            <?php while ($u = $recentUsers->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $u['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($u['username']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td>
                                        <span class="tag <?php echo ($u['role'] === 'admin') ? 'tag-admin' : 'tag-student'; ?>">
                                            <?php echo ucfirst(htmlspecialchars($u['role'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="tag <?php echo ($u['status'] === 'active') ? 'tag-active' : 'tag-inactive'; ?>">
                                            <?php echo ucfirst(htmlspecialchars($u['status'])); ?>
                                        </span>
                                    </td>
                                    <td style="font-size:12px; color:#666;">
                                        <?php echo $u['last_login'] ? date('d M Y, h:i A', strtotime($u['last_login'])) : 'Never'; ?>
                                    </td>
                                    <td style="font-size:12px; color:#666;">
                                        <?php echo date('d M Y', strtotime($u['created_at'])); ?>
                                    </td>
                                    <td style="text-align:center;">
                                        <?php if ($u['id'] == $_SESSION['user_id']): ?>
                                            <span style="font-size:11px; color:#999; font-style:italic;">Current User</span>
                                        <?php else: ?>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Change status for <?php echo addslashes(htmlspecialchars($u['username'])); ?>?');">
                                                <input type="hidden" name="action" value="toggle_user_status">
                                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                <button type="submit" class="btn-toggle">
                                                    <?php echo ($u['status'] === 'active') ? 'Deactivate' : 'Activate'; ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 4. AUDIT LOGS VIEWER (Requirement 4: Traceability & Audit Logging) -->
        <div class="panel">
            <div class="panel-title">
                <span>🛡️ Security & Activity Audit Log</span>
                <span style="font-size:12px; font-weight:normal; color:#888;">Logged actions from <code>audit_logs</code> table</span>
            </div>
            <div style="overflow-x:auto;">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th width="70">Log ID</th>
                            <th>User</th>
                            <th>Action Type</th>
                            <th>Details</th>
                            <th>IP Address</th>
                            <th width="180">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($auditLogs && $auditLogs->num_rows > 0): ?>
                            <?php while ($log = $auditLogs->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $log['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($log['username'] ?? 'System/Guest'); ?></strong></td>
                                    <td>
                                        <span class="tag tag-admin" style="font-family:monospace;">
                                            <?php echo htmlspecialchars($log['action']); ?>
                                        </span>
                                    </td>
                                    <td style="font-size:12px; color:#444;">
                                        <?php echo htmlspecialchars($log['details']); ?>
                                    </td>
                                    <td style="font-size:12px; font-family:monospace; color:#666;">
                                        <?php echo htmlspecialchars($log['ip_address']); ?>
                                    </td>
                                    <td style="font-size:12px; color:#777;">
                                        <?php echo date('d M Y, h:i:s A', strtotime($log['created_at'])); ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align:center; padding:20px; color:#888;">No audit logs recorded yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <footer align="center" style="margin-top:30px;">
        <h4>@ 2026 StudentHub - Practical 14 Role-Based Admin Dashboard</h4>
    </footer>

</body>
</html>
