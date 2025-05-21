<?php
require 'auth_check.php';
require '../dbconnection.php';

// Check if user is admin
if (!$_SESSION['is_admin']) {
    header("Location: ../index.php");
    exit();
}

// Handle verification status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_user'])) {
    $studentId = $_POST['student_id'];
    $stmt = $connection->prepare("UPDATE students SET is_verified = 1 WHERE student_id = ?");
    $stmt->execute([$studentId]);
    header("Location: users.php");
    exit();
}

// Fetch all students with their details
$students = $connection->query("
    SELECT 
        s.*,
        COUNT(t.task_id) as total_tasks,
        COUNT(CASE WHEN t.status = 'completed' THEN 1 END) as completed_tasks
    FROM students s
    LEFT JOIN tasks t ON s.student_id = t.assigned_to
    GROUP BY s.student_id
    ORDER BY s.first_name ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../style.css" rel="stylesheet">
</head>
<body>
    <div class="theme-toggle">
        <button id="themeToggleBtn" aria-label="Toggle dark/light mode">
            <i id="themeIcon" class="bi bi-moon"></i>
        </button>
    </div>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h4>Dashboard Menu</h4>
            </div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="task.php" class="nav-link"><i class="bi bi-list-task"></i><span>Tasks</span></a>
                </li>
                <li class="nav-item active">
                    <a href="users.php" class="nav-link"><i class="bi bi-people"></i><span>Users</span></a>
                </li>
                <li class="nav-item">
                    <a href="../index.php" class="nav-link"><i class="bi bi-graph-up"></i><span>Analytics</span></a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="dashboard-header">
                <h3>Student Management</h3>
            </div>

            <div class="student-grid">
                <?php foreach ($students as $student): ?>
                <div class="student-card">
                    <div class="student-card-header">
                        <div class="student-info">
                            <img src="<?= $student['profile_image'] ? '../profiles/' . $student['profile_image'] : '../assets/images/default.png' ?>" 
                                 alt="Profile" 
                                 class="student-avatar">
                            <div>
                                <div class="student-name"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></div>
                                <div class="student-id">ID: <?= htmlspecialchars($student['student_id']) ?></div>
                            </div>
                        </div>
                        <span class="status-badge <?= $student['is_verified'] ? 'verified' : 'not-verified' ?>">
                            <?= $student['is_verified'] ? 'Verified' : 'Not Verified' ?>
                        </span>
                    </div>
                    <div class="student-card-body">
                        <div class="info-item">
                            <i class="bi bi-book"></i>
                            <span><?= htmlspecialchars($student['course']) ?></span>
                        </div>
                        <div class="info-item">
                            <i class="bi bi-envelope"></i>
                            <span><?= htmlspecialchars($student['email']) ?></span>
                        </div>
                        <div class="task-progress">
                            <div class="progress">
                                <?php 
                                $completion = $student['total_tasks'] > 0 
                                    ? ($student['completed_tasks'] / $student['total_tasks']) * 100 
                                    : 0;
                                ?>
                                <div class="progress-bar" role="progressbar" 
                                     style="width: <?= $completion ?>%"
                                     aria-valuenow="<?= $completion ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                </div>
                            </div>
                            <small><?= $student['completed_tasks'] ?>/<?= $student['total_tasks'] ?> tasks completed</small>
                        </div>
                    </div>
                    <div class="student-card-footer">
                        <?php if (!$student['is_verified']): ?>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="student_id" value="<?= $student['student_id'] ?>">
                            <button type="submit" name="verify_user" class="btn btn-sm btn-success">
                                <i class="bi bi-check-circle"></i> Verify
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Student Details Modal -->
    <div class="modal fade" id="studentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content quantum-modal">
                <div class="modal-header quantum-modal-header">
                    <h5 class="modal-title">Student Details</h5>
                    <button type="button" class="btn-close quantum-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body quantum-modal-body">
                    <div class="student-details">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <img src="" alt="Profile" class="quantum-avatar" id="modalStudentAvatar">
                            </div>
                            <div class="col-md-8">
                                <h4 class="quantum-gradient-text" id="modalStudentName"></h4>
                                <div class="quantum-info-grid mt-4">
                                    <div class="quantum-info-item">
                                        <span class="quantum-info-label">Student ID</span>
                                        <span class="quantum-info-value" id="modalStudentId"></span>
                                    </div>
                                    <div class="quantum-info-item">
                                        <span class="quantum-info-label">Email</span>
                                        <span class="quantum-info-value" id="modalStudentEmail"></span>
                                    </div>
                                    <div class="quantum-info-item">
                                        <span class="quantum-info-label">Course</span>
                                        <span class="quantum-info-value" id="modalStudentCourse"></span>
                                    </div>
                                    <div class="quantum-info-item">
                                        <span class="quantum-info-label">Phone</span>
                                        <span class="quantum-info-value" id="modalStudentPhone"></span>
                                    </div>
                                    <div class="quantum-info-item">
                                        <span class="quantum-info-label">Status</span>
                                        <span class="quantum-info-value" id="modalStudentStatus"></span>
                                    </div>
                                    <div class="quantum-info-item">
                                        <span class="quantum-info-label">Address</span>
                                        <span class="quantum-info-value" id="modalStudentAddress"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="quantum-divider"></div>

                        <div class="task-statistics">
                            <h5 class="mb-4">Task Statistics</h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="quantum-info-item">
                                        <span class="quantum-info-label">Total Tasks</span>
                                        <span class="quantum-info-value" id="modalTotalTasks">0</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="quantum-info-item">
                                        <span class="quantum-info-label">Completed</span>
                                        <span class="quantum-info-value" id="modalCompletedTasks">0</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="quantum-info-item">
                                        <span class="quantum-info-label">Pending</span>
                                        <span class="quantum-info-value" id="modalPendingTasks">0</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="quantum-divider"></div>

                        <div class="recent-tasks">
                            <h5 class="mb-4">Recent Tasks</h5>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Task</th>
                                            <th>Due Date</th>
                                            <th>Status</th>
                                            <th>Submission</th>
                                        </tr>
                                    </thead>
                                    <tbody id="modalRecentTasks">
                                        <!-- Tasks will be loaded dynamically -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer quantum-modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Theme toggle functionality
    function setTheme(mode) {
        document.body.classList.toggle('light-mode', mode === 'light');
        document.getElementById('themeIcon').className = mode === 'light' ? 'bi bi-sun' : 'bi bi-moon';
        localStorage.setItem('theme', mode);
    }
    (function() {
        const saved = localStorage.getItem('theme');
        const prefersLight = window.matchMedia('(prefers-color-scheme: light)').matches;
        setTheme(saved ? saved : (prefersLight ? 'light' : 'dark'));
    })();
    document.getElementById('themeToggleBtn').onclick = function() {
        const isLight = document.body.classList.contains('light-mode');
        setTheme(isLight ? 'dark' : 'light');
    };
    </script>
</body>
</html> 