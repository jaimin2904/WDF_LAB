<?php
/**
 * Practical 13: Asynchronous AJAX/Fetch-Based Student Management CRUD
 * 
 * Features:
 * - Pure asynchronous Fetch API (Zero full-page reloads)
 * - Live Search with Debounce (Intermediate Extension)
 * - HTTP Status Codes & Centralized Error Handling (Advanced Extension)
 * - Live Network Activity Inspector (Inspect JSON request/response)
 * - Add & Edit Modal with validation
 * - Delete confirmation with DOM animation
 * - Toast notification feedback system
 */

session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$userRole   = $_SESSION['role'] ?? 'guest';
$userName   = $_SESSION['username'] ?? 'Guest User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Practical 13: AJAX/Fetch API CRUD</title>
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --primary: #1f4e5b;
            --primary-light: #eaf4fc;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
            --dark: #333333;
            --border: #ccd6dd;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 10px;
            background-color: #f7f9fa;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            padding: 20px;
        }

        .page-badge {
            display: inline-block;
            background: #1f4e5b;
            color: #ffffff;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        /* Toolbar */
        .toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: space-between;
            align-items: center;
            background: #eef3f7;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #d9e2ec;
        }

        .search-group {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-grow: 1;
            max-width: 600px;
        }

        .search-input {
            flex-grow: 1;
            padding: 9px 12px;
            border: 1px solid #bccad6;
            border-radius: 6px;
            font-size: 14px;
        }

        .select-filter {
            padding: 9px 12px;
            border: 1px solid #bccad6;
            border-radius: 6px;
            font-size: 14px;
            background: white;
        }

        .btn {
            padding: 9px 16px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-primary {
            background-color: #1f4e5b;
            color: white;
            box-shadow: 0 2px 4px rgba(31,78,91,0.25);
        }
        .btn-primary:hover { background-color: #15353e; }

        .btn-success { background-color: #28a745; color: white; }
        .btn-success:hover { background-color: #218838; }

        .btn-danger { background-color: #dc3545; color: white; }
        .btn-danger:hover { background-color: #c82333; }

        .btn-secondary { background-color: #6c757d; color: white; }
        .btn-secondary:hover { background-color: #5a6268; }

        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
            border-radius: 4px;
        }

        /* Stats Cards */
        .stats-row {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .stat-card {
            flex: 1;
            min-width: 180px;
            background: #ffffff;
            border: 1px solid #e1e8ed;
            border-left: 5px solid #1f4e5b;
            padding: 12px 16px;
            border-radius: 6px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        }
        .stat-number {
            font-size: 22px;
            font-weight: bold;
            color: #1f4e5b;
        }
        .stat-label {
            font-size: 13px;
            color: #657786;
        }

        /* Table */
        .table-responsive {
            overflow-x: auto;
            border: 1px solid #e1e8ed;
            border-radius: 6px;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            text-align: left;
        }

        table.data-table th {
            background-color: #e9ecef;
            color: #333333;
            padding: 12px;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
        }

        table.data-table td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }

        table.data-table tr:hover {
            background-color: #f8fafc;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        .badge-ce { background: #d1ecf1; color: #0c5460; }
        .badge-it { background: #d4edda; color: #155724; }
        .badge-ec { background: #fff3cd; color: #856404; }
        .badge-me { background: #f8d7da; color: #721c24; }
        .badge-default { background: #e2e3e5; color: #383d41; }

        /* Toast Notifications */
        #toastContainer {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .toast {
            min-width: 280px;
            max-width: 380px;
            padding: 12px 18px;
            border-radius: 6px;
            color: white;
            font-size: 14px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            animation: slideIn 0.3s ease-out;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .toast-success { background: #28a745; }
        .toast-error { background: #dc3545; }
        .toast-info { background: #17a2b8; }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.55);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-overlay.active {
            display: flex;
        }
        .modal-box {
            background: white;
            border-radius: 8px;
            width: 100%;
            max-width: 550px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.25);
            animation: popIn 0.25s ease-out;
        }
        @keyframes popIn {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        .modal-header {
            padding: 15px 20px;
            background: #1f4e5b;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }
        .modal-header h3 { margin: 0; font-size: 18px; }
        .modal-close {
            background: transparent;
            border: none;
            color: white;
            font-size: 22px;
            cursor: pointer;
            box-shadow: none;
            padding: 0;
        }
        .modal-body {
            padding: 20px;
        }
        .modal-footer {
            padding: 12px 20px;
            background: #f1f3f5;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            border-bottom-left-radius: 8px;
            border-bottom-right-radius: 8px;
        }

        .form-row {
            margin-bottom: 12px;
        }
        .form-row label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 4px;
            color: #333;
        }
        .form-control {
            width: 100%;
            box-sizing: border-box;
            padding: 8px 10px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 14px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        /* Network Activity Panel */
        .api-monitor {
            margin-top: 25px;
            background: #1e1e1e;
            color: #d4d4d4;
            border-radius: 8px;
            padding: 15px;
            font-family: Consolas, monospace;
            font-size: 13px;
        }
        .api-monitor-header {
            display: flex;
            justify-content: space-between;
            color: #4ec9b0;
            font-weight: bold;
            margin-bottom: 10px;
            padding-bottom: 6px;
            border-bottom: 1px solid #333;
        }
        .log-line {
            margin: 4px 0;
            word-break: break-all;
        }
        .log-line .method { color: #569cd6; font-weight: bold; }
        .log-line .url { color: #dcdcaa; }
        .log-line .code-200, .log-line .code-201 { color: #4ec9b0; font-weight: bold; }
        .log-line .code-400, .log-line .code-404, .log-line .code-409, .log-line .code-500 { color: #f44747; font-weight: bold; }

        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s ease-in-out infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

    <div id="header">
        <h1 align="center">STUDENTHUB</h1>
    </div>

    <br>

    <!-- Navigation -->
    <div align="center">
        <a href="home.HTML"><button type="button">Home</button></a>
        <a href="events.HTML"><button type="button">Events</button></a>
        <a href="practical11_students.php"><button type="button">Students (Standard CRUD)</button></a>
        <a href="practical12_events.php"><button type="button">Event Management</button></a>
        <a href="practical13_ajax.php"><button type="button" style="background:#1f4e5b;color:white;">AJAX CRUD (P13)</button></a>
        <a href="practical14_dashboard.php"><button type="button">Admin Dashboard (P14)</button></a>
        <?php if ($isLoggedIn): ?>
            <a href="practical10_logout.php"><button type="button" style="color:red;">Logout (<?php echo htmlspecialchars($userName); ?>)</button></a>
        <?php else: ?>
            <a href="practical10_login.php"><button type="button">Login</button></a>
        <?php endif; ?>
    </div>

    <br><hr><br>

    <!-- Toast Notification Container -->
    <div id="toastContainer"></div>

    <div class="container">
        <span class="page-badge">PRACTICAL 13: ASYNC AJAX & FETCH API</span>
        <h2>Student Management (Zero Page Reload CRUD)</h2>
        <p style="color:#666; font-size:14px; margin-top:-5px;">
            This page communicates with <code>practical13_api.php</code> asynchronously via JavaScript <strong>Fetch API</strong>. 
            All search, filter, insert, update, and delete actions occur live without refreshing the browser.
        </p>

        <!-- Stats Overview -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-number" id="statTotalStudents">--</div>
                <div class="stat-label">Total Active Students</div>
            </div>
            <div class="stat-card" style="border-left-color: #28a745;">
                <div class="stat-number" id="statCE">--</div>
                <div class="stat-label">CE Department</div>
            </div>
            <div class="stat-card" style="border-left-color: #17a2b8;">
                <div class="stat-number" id="statIT">--</div>
                <div class="stat-label">IT Department</div>
            </div>
            <div class="stat-card" style="border-left-color: #ffc107;">
                <div class="stat-number" id="statLastAction">Ready</div>
                <div class="stat-label">Last AJAX Status</div>
            </div>
        </div>

        <!-- Toolbar: Search with Debounce, Filter, and Add Button -->
        <div class="toolbar">
            <div class="search-group">
                <input type="text" id="searchInput" class="search-input" placeholder="🔍 Search by name, email, or phone (Live Debounce)...">
                <select id="courseFilter" class="select-filter">
                    <option value="">All Departments</option>
                    <option value="CE">Computer Eng (CE)</option>
                    <option value="IT">Information Tech (IT)</option>
                    <option value="EC">Electronics (EC)</option>
                    <option value="ME">Mechanical (ME)</option>
                </select>
                <button type="button" id="btnRefresh" class="btn btn-secondary btn-sm" title="Reload data via API">
                    ↻ Refresh
                </button>
            </div>
            <div>
                <button type="button" id="btnOpenAddModal" class="btn btn-success">
                    + Add New Student
                </button>
            </div>
        </div>

        <!-- Table View -->
        <div class="table-responsive">
            <table class="data-table" id="studentsTable">
                <thead>
                    <tr>
                        <th width="60">ID</th>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Course</th>
                        <th>Year</th>
                        <th>Gender</th>
                        <th>Address</th>
                        <th width="150" style="text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody id="studentsTableBody">
                    <tr>
                        <td colspan="9" style="text-align:center; padding: 40px; color: #888;">
                            <div class="spinner" style="border-color:#1f4e5b; border-top-color:transparent; width:24px; height:24px;"></div>
                            <p style="margin-top:10px;">Loading student records from JSON API...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Practical 13 Advanced Extension: Network Activity & JSON Inspector -->
        <div class="api-monitor">
            <div class="api-monitor-header">
                <span>📡 Live Fetch API Network Activity & Inspector</span>
                <span id="networkPulse" style="color:#28a745;">● Live Connected</span>
            </div>
            <div id="apiLogOutput" style="max-height: 140px; overflow-y: auto;">
                <div class="log-line" style="color:#888;">[Ready] Waiting for fetch requests to <code>practical13_api.php</code>...</div>
            </div>
        </div>

    </div>

    <!-- ADD / EDIT MODAL -->
    <div class="modal-overlay" id="studentModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 id="modalTitle">Add New Student</h3>
                <button type="button" class="modal-close" id="modalCloseBtn">&times;</button>
            </div>
            <form id="studentForm">
                <div class="modal-body">
                    <input type="hidden" id="studentId" name="id" value="">
                    <input type="hidden" id="formAction" name="action" value="create">

                    <div class="form-row">
                        <label for="modalFullname">Full Name *</label>
                        <input type="text" id="modalFullname" name="fullname" class="form-control" required placeholder="e.g. John Doe">
                    </div>

                    <div class="form-grid">
                        <div class="form-row">
                            <label for="modalEmail">Email Address *</label>
                            <input type="email" id="modalEmail" name="email" class="form-control" required placeholder="john@example.com">
                        </div>
                        <div class="form-row">
                            <label for="modalPhone">Phone (10 digits) *</label>
                            <input type="text" id="modalPhone" name="phone" class="form-control" required pattern="[0-9]{10}" placeholder="9876543210">
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-row">
                            <label for="modalCourse">Course / Department *</label>
                            <select id="modalCourse" name="course" class="form-control" required>
                                <option value="">Select Course</option>
                                <option value="CE">CE - Computer Engineering</option>
                                <option value="IT">IT - Information Technology</option>
                                <option value="EC">EC - Electronics & Comm.</option>
                                <option value="ME">ME - Mechanical Engineering</option>
                            </select>
                        </div>
                        <div class="form-row">
                            <label for="modalYear">Year of Study *</label>
                            <select id="modalYear" name="year" class="form-control" required>
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <label>Gender *</label>
                        <div style="display:flex; gap: 20px; padding-top: 4px;">
                            <label><input type="radio" name="gender" value="Male" checked> Male</label>
                            <label><input type="radio" name="gender" value="Female"> Female</label>
                            <label><input type="radio" name="gender" value="Other"> Other</label>
                        </div>
                    </div>

                    <div class="form-row">
                        <label for="modalAddress">Address</label>
                        <textarea id="modalAddress" name="address" class="form-control" rows="2" placeholder="City, State"></textarea>
                    </div>

                    <div id="modalErrorMsg" style="display:none; color:#dc3545; background:#f8d7da; padding:8px 12px; border-radius:4px; font-size:13px; margin-top:10px;"></div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="modalCancelBtn">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="modalSubmitBtn">
                        <span id="btnSubmitText">Save Student</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- DELETE CONFIRMATION MODAL -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-box" style="max-width: 400px;">
            <div class="modal-header" style="background:#dc3545;">
                <h3>Confirm Deletion</h3>
                <button type="button" class="modal-close" id="deleteModalCloseBtn">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteStudentName">this student</strong>?</p>
                <p style="font-size:13px; color:#888;">This action will soft-delete the record from the database asynchronously.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="deleteCancelBtn">Cancel</button>
                <button type="button" class="btn btn-danger" id="deleteConfirmBtn">Yes, Delete</button>
            </div>
        </div>
    </div>

    <br><br>
    <footer align="center">
        <h4>@ 2026 StudentHub - Practical 13 Asynchronous AJAX CRUD</h4>
    </footer>

    <!-- ======================================================== -->
    <!-- JAVASCRIPT: Fetch API Async CRUD Logic & Debounced Search -->
    <!-- ======================================================== -->
    <script>
        const API_URL = 'practical13_api.php';

        // DOM elements
        const tableBody       = document.getElementById('studentsTableBody');
        const searchInput     = document.getElementById('searchInput');
        const courseFilter    = document.getElementById('courseFilter');
        const btnRefresh      = document.getElementById('btnRefresh');
        const studentModal    = document.getElementById('studentModal');
        const deleteModal     = document.getElementById('deleteModal');
        const studentForm     = document.getElementById('studentForm');
        const apiLogOutput    = document.getElementById('apiLogOutput');
        const modalErrorMsg   = document.getElementById('modalErrorMsg');
        const modalSubmitBtn  = document.getElementById('modalSubmitBtn');
        const btnSubmitText   = document.getElementById('btnSubmitText');

        // State
        let currentStudents = [];
        let deleteTargetId = null;
        let searchDebounceTimer = null;

        // Toast notification system
        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `
                <span>${escapeHtml(message)}</span>
                <span style="cursor:pointer; margin-left:10px; font-weight:bold;">&times;</span>
            `;
            toast.querySelector('span:last-child').onclick = () => toast.remove();
            container.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                toast.style.transition = 'all 0.3s ease-out';
                setTimeout(() => toast.remove(), 300);
            }, 3500);
        }

        // Live Network Logger
        function logNetwork(method, url, statusCode, message) {
            const time = new Date().toLocaleTimeString();
            const logItem = document.createElement('div');
            logItem.className = 'log-line';
            logItem.innerHTML = `
                <span style="color:#6a9955;">[${time}]</span> 
                <span class="method">${method}</span> 
                <span class="url">${escapeHtml(url)}</span> 
                &rarr; <span class="code-${statusCode}">${statusCode}</span> 
                <span style="color:#d4d4d4;">(${escapeHtml(message)})</span>
            `;
            apiLogOutput.insertBefore(logItem, apiLogOutput.firstChild);
            document.getElementById('statLastAction').textContent = `${statusCode} OK`;
        }

        // HTML escaping
        function escapeHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Department Badge Helper
        function getBadgeClass(course) {
            switch(course) {
                case 'CE': return 'badge-ce';
                case 'IT': return 'badge-it';
                case 'EC': return 'badge-ec';
                case 'ME': return 'badge-me';
                default:   return 'badge-default';
            }
        }

        // ========================================================
        // 1. FETCH & RENDER STUDENTS (Asynchronous GET)
        // ========================================================
        async function loadStudents() {
            const searchVal = encodeURIComponent(searchInput.value.trim());
            const courseVal = encodeURIComponent(courseFilter.value);
            const queryUrl = `${API_URL}?action=list&search=${searchVal}&course=${courseVal}`;

            tableBody.innerHTML = `
                <tr>
                    <td colspan="9" style="text-align:center; padding: 30px; color:#666;">
                        <div class="spinner" style="border-color:#1f4e5b; border-top-color:transparent; width:20px; height:20px;"></div>
                        <span style="margin-left:10px;">Fetching records...</span>
                    </td>
                </tr>
            `;

            try {
                const response = await fetch(queryUrl);
                const result = await response.json();

                logNetwork('GET', queryUrl, response.status, result.message || 'OK');

                if (response.ok && result.status === 'success') {
                    currentStudents = result.data.students || [];
                    renderTable(currentStudents);
                    updateStats();
                } else {
                    tableBody.innerHTML = `<tr><td colspan="9" style="text-align:center; color:red; padding:20px;">Failed to load: ${escapeHtml(result.message)}</td></tr>`;
                    showToast(result.message, 'error');
                }
            } catch (error) {
                tableBody.innerHTML = `<tr><td colspan="9" style="text-align:center; color:red; padding:20px;">Network Error: ${escapeHtml(error.message)}</td></tr>`;
                logNetwork('GET', queryUrl, 500, error.message);
                showToast('Network error while fetching students', 'error');
            }
        }

        function renderTable(students) {
            if (!students || students.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="9" style="text-align:center; padding: 40px; color: #888;">
                            <strong>No student records found.</strong>
                            <p style="margin:5px 0 0; font-size:13px;">Try adjusting your search criteria or click '+ Add New Student'.</p>
                        </td>
                    </tr>
                `;
                return;
            }

            tableBody.innerHTML = students.map(s => `
                <tr id="row-student-${s.id}">
                    <td><strong>#${s.id}</strong></td>
                    <td><strong>${escapeHtml(s.fullname)}</strong></td>
                    <td>${escapeHtml(s.email)}</td>
                    <td>${escapeHtml(s.phone)}</td>
                    <td><span class="badge ${getBadgeClass(s.course)}">${escapeHtml(s.course)}</span></td>
                    <td>Year ${escapeHtml(s.year)}</td>
                    <td>${escapeHtml(s.gender)}</td>
                    <td>${escapeHtml(s.address || '-')}</td>
                    <td style="text-align:center;">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="openEditModal(${s.id})">Edit</button>
                        <button type="button" class="btn btn-danger btn-sm" onclick="openDeleteModal(${s.id}, '${escapeHtml(s.fullname)}')">Delete</button>
                    </td>
                </tr>
            `).join('');
        }

        // Update Summary Cards
        async function updateStats() {
            try {
                const response = await fetch(`${API_URL}?action=stats`);
                const res = await response.json();
                if (response.ok && res.status === 'success') {
                    document.getElementById('statTotalStudents').textContent = res.data.total_students;
                    const courses = res.data.course_breakdown || [];
                    const ce = courses.find(c => c.course === 'CE')?.count || 0;
                    const it = courses.find(c => c.course === 'IT')?.count || 0;
                    document.getElementById('statCE').textContent = ce;
                    document.getElementById('statIT').textContent = it;
                }
            } catch (err) {
                console.error('Stats error:', err);
            }
        }

        // ========================================================
        // 2. LIVE SEARCH WITH DEBOUNCE (Intermediate Requirement)
        // ========================================================
        searchInput.addEventListener('input', () => {
            clearTimeout(searchDebounceTimer);
            searchDebounceTimer = setTimeout(() => {
                loadStudents();
            }, 300); // 300ms debounce
        });

        courseFilter.addEventListener('change', () => {
            loadStudents();
        });

        btnRefresh.addEventListener('click', () => {
            loadStudents();
            showToast('Refreshed student records', 'info');
        });

        // ========================================================
        // 3. CREATE & UPDATE (Asynchronous POST via Fetch API)
        // ========================================================
        const btnOpenAddModal = document.getElementById('btnOpenAddModal');
        const modalCloseBtn   = document.getElementById('modalCloseBtn');
        const modalCancelBtn  = document.getElementById('modalCancelBtn');

        btnOpenAddModal.addEventListener('click', () => {
            studentForm.reset();
            document.getElementById('studentId').value = '';
            document.getElementById('formAction').value = 'create';
            document.getElementById('modalTitle').textContent = 'Add New Student';
            btnSubmitText.textContent = 'Save Student';
            modalErrorMsg.style.display = 'none';
            studentModal.classList.add('active');
        });

        function closeModal() {
            studentModal.classList.remove('active');
        }
        modalCloseBtn.addEventListener('click', closeModal);
        modalCancelBtn.addEventListener('click', closeModal);

        window.openEditModal = async function(id) {
            modalErrorMsg.style.display = 'none';
            btnSubmitText.textContent = 'Updating...';

            try {
                const response = await fetch(`${API_URL}?action=get&id=${id}`);
                const res = await response.json();
                logNetwork('GET', `${API_URL}?action=get&id=${id}`, response.status, res.message);

                if (response.ok && res.status === 'success') {
                    const s = res.data;
                    document.getElementById('studentId').value = s.id;
                    document.getElementById('formAction').value = 'update';
                    document.getElementById('modalTitle').textContent = `Edit Student #${s.id}`;
                    document.getElementById('modalFullname').value = s.fullname;
                    document.getElementById('modalEmail').value = s.email;
                    document.getElementById('modalPhone').value = s.phone;
                    document.getElementById('modalCourse').value = s.course;
                    document.getElementById('modalYear').value = s.year;
                    document.getElementById('modalAddress').value = s.address || '';

                    // Gender radio
                    const radios = document.getElementsByName('gender');
                    for (const r of radios) {
                        r.checked = (r.value === s.gender);
                    }

                    btnSubmitText.textContent = 'Update Student';
                    studentModal.classList.add('active');
                } else {
                    showToast(res.message, 'error');
                }
            } catch (err) {
                showToast('Failed to load student details: ' + err.message, 'error');
            }
        };

        // Submit Form Handler
        studentForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            modalErrorMsg.style.display = 'none';

            const action = document.getElementById('formAction').value;
            const payload = {
                action: action,
                id: document.getElementById('studentId').value,
                fullname: document.getElementById('modalFullname').value.trim(),
                email: document.getElementById('modalEmail').value.trim(),
                phone: document.getElementById('modalPhone').value.trim(),
                course: document.getElementById('modalCourse').value,
                year: document.getElementById('modalYear').value,
                gender: document.querySelector('input[name="gender"]:checked')?.value,
                address: document.getElementById('modalAddress').value.trim()
            };

            // Client-side quick check
            if (!/^[0-9]{10}$/.test(payload.phone)) {
                modalErrorMsg.textContent = 'Phone must be exactly 10 numeric digits.';
                modalErrorMsg.style.display = 'block';
                return;
            }

            modalSubmitBtn.disabled = true;
            btnSubmitText.textContent = 'Saving...';

            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const res = await response.json();

                logNetwork('POST', `${API_URL} (action: ${action})`, response.status, res.message);

                if (response.ok && res.status === 'success') {
                    showToast(res.message, 'success');
                    closeModal();
                    loadStudents();
                } else {
                    modalErrorMsg.textContent = res.message || 'Operation failed.';
                    modalErrorMsg.style.display = 'block';
                    showToast(res.message, 'error');
                }
            } catch (err) {
                modalErrorMsg.textContent = 'Network error: ' + err.message;
                modalErrorMsg.style.display = 'block';
                logNetwork('POST', API_URL, 500, err.message);
                showToast('Network error during save', 'error');
            } finally {
                modalSubmitBtn.disabled = false;
                btnSubmitText.textContent = (action === 'create') ? 'Save Student' : 'Update Student';
            }
        });

        // ========================================================
        // 4. ASYNCHRONOUS DELETE (Zero Page Reload)
        // ========================================================
        const deleteCloseBtn   = document.getElementById('deleteModalCloseBtn');
        const deleteCancelBtn  = document.getElementById('deleteCancelBtn');
        const deleteConfirmBtn = document.getElementById('deleteConfirmBtn');

        window.openDeleteModal = function(id, name) {
            deleteTargetId = id;
            document.getElementById('deleteStudentName').textContent = name;
            deleteModal.classList.add('active');
        };

        function closeDeleteModal() {
            deleteModal.classList.remove('active');
            deleteTargetId = null;
        }

        deleteCloseBtn.addEventListener('click', closeDeleteModal);
        deleteCancelBtn.addEventListener('click', closeDeleteModal);

        deleteConfirmBtn.addEventListener('click', async () => {
            if (!deleteTargetId) return;

            deleteConfirmBtn.disabled = true;
            deleteConfirmBtn.textContent = 'Deleting...';

            const url = `${API_URL}?action=delete&id=${deleteTargetId}`;

            try {
                const response = await fetch(url, { method: 'POST' });
                const res = await response.json();

                logNetwork('POST', url, response.status, res.message);

                if (response.ok && res.status === 'success') {
                    showToast(res.message, 'success');
                    
                    // Smoothly animate removal from DOM table without reload
                    const row = document.getElementById(`row-student-${deleteTargetId}`);
                    if (row) {
                        row.style.transition = 'all 0.4s ease';
                        row.style.backgroundColor = '#f8d7da';
                        row.style.opacity = '0';
                        setTimeout(() => {
                            row.remove();
                            updateStats();
                        }, 400);
                    }
                    closeDeleteModal();
                } else {
                    showToast(res.message, 'error');
                }
            } catch (err) {
                showToast('Failed to delete student: ' + err.message, 'error');
                logNetwork('POST', url, 500, err.message);
            } finally {
                deleteConfirmBtn.disabled = false;
                deleteConfirmBtn.textContent = 'Yes, Delete';
            }
        });

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', () => {
            loadStudents();
        });
    </script>
</body>
</html>
