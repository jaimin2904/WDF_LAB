<?php
/**
 * Practical 10: Student Dashboard (Role-Based Access)
 * Only accessible to logged-in students.
 */

require_once 'auth_check.php';

// Role check - only students can access this
if ($_SESSION['role'] !== 'student') {
    header('Location: practical14_dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Student Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .welcome-msg {
            color: #155724;
            background-color: #d4edda;
            border: 2px solid #155724;
            padding: 15px;
            border-radius: 8px;
            margin: 10px auto;
            max-width: 500px;
        }
        .session-info {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
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
        <a href="profile.HTML"><button type="button">Profile</button></a>
        <a href="events.HTML"><button type="button">Events</button></a>
        <a href="Attendance.HTML"><button type="button">Attendance</button></a>
        <a href="result.HTML"><button type="button">Result</button></a>
        <a href="timetable.html"><button type="button">TimeTable</button></a>
        <a href="feedback.HTML"><button type="button">FeedBack</button></a>
        <a href="practical10_logout.php"><button type="button">Logout</button></a>
    </div>

    <br>
    <hr>
    <br>

    <div align="center">

        <div class="welcome-msg">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
            <p>Role: <strong><?php echo ucfirst(htmlspecialchars($_SESSION['role'])); ?></strong></p>
            <p class="session-info">
                Logged in since: <?php echo date('d M Y, h:i A', $_SESSION['login_time']); ?>
            </p>
            <p class="session-info">
                Session will timeout after 30 minutes of inactivity.
            </p>
        </div>

        <br>

        <h2><u>Student Dashboard</u></h2>

        <br>

        <div class="card">
            <h3>Quick Links</h3>
            <br>
            <a href="profile.HTML"><button type="button">My Profile</button></a>
            <a href="result.HTML"><button type="button">My Results</button></a>
            <a href="Attendance.HTML"><button type="button">My Attendance</button></a>
            <a href="timetable.html"><button type="button">Timetable</button></a>
            <a href="events.HTML"><button type="button">Events</button></a>
            <a href="feedback.HTML"><button type="button">Submit Feedback</button></a>
        </div>

        <br>

        <a href="practical10_logout.php">
            <button type="button">Logout</button>
        </a>

    </div>

    <br>
    <hr>

    <footer align="center">
        <h4>@ 2026 Jaimin Khambhaliya</h4>
    </footer>

</body>
</html>
