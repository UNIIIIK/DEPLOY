<?php
require 'auth_check.php';
require '../dbconnection.php';

// Check if user is admin
if (!$_SESSION['is_admin']) {
    header("Location: ../index.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_task'])) {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $due_date = $_POST['due_date'];
        $assignedToArray = $_POST['assigned_to'] ?? [];

        // Validate assignedToArray is array and not empty
        if (!is_array($assignedToArray) || count($assignedToArray) === 0) {
            $_SESSION['error_message'] = 'Please select at least one student to assign the task.';
            header("Location: task.php");
            exit();
        }

        // Insert a task for each selected student
        foreach ($assignedToArray as $assigned_to) {
            $stmt = $connection->prepare("INSERT INTO tasks (title, description, due_date, assigned_to, status) VALUES (?, ?, ?, ?, 'pending')");
            $stmt->execute([$title, $description, $due_date, $assigned_to]);
        }

        header("Location: task.php");
        exit();
    } elseif (isset($_POST['edit_task'])) {
        $taskId = $_POST['task_id'];
        $title = $_POST['title'];
        $description = $_POST['description'];
        $due_date = $_POST['due_date'];
        $assigned_to = $_POST['assigned_to'];

        $stmt = $connection->prepare("UPDATE tasks SET title = ?, description = ?, due_date = ?, assigned_to = ? WHERE task_id = ?");
        $stmt->execute([$title, $description, $due_date, $assigned_to, $taskId]);

        header("Location: task.php");
        exit();
    } elseif (isset($_POST['delete_task'])) {
        $taskId = $_POST['task_id'];

        $stmt = $connection->prepare("DELETE FROM tasks WHERE task_id = ?");
        $stmt->execute([$taskId]);

        header("Location: task.php");
        exit();
    } elseif (isset($_POST['mark_completed'])) {
        $taskId = $_POST['task_id'];

        $stmt = $connection->prepare("UPDATE tasks SET status = 'completed' WHERE task_id = ?");
        $stmt->execute([$taskId]);

        header("Location: task.php");
        exit();
    }
}

// Fetch tasks with submission info and assigned student names
$tasks = $connection->query("
    SELECT t.*, s.first_name, s.last_name, ts.submission_id, ts.file_path, ts.submitted_at
    FROM tasks t
    LEFT JOIN students s ON t.assigned_to = s.student_id
    LEFT JOIN task_submissions ts ON t.task_id = ts.task_id
    ORDER BY t.due_date ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch students for dropdown
$students = $connection->query("
    SELECT student_id, first_name, last_name
    FROM students
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Task Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet" />
    <link href="../style.css" rel="stylesheet" />
</head>
<body>
    <div class="theme-toggle" style="position:fixed;top:20px;right:30px;z-index:999;">
      <button id="themeToggleBtn" aria-label="Toggle dark/light mode" style="background:none;border:none;cursor:pointer;font-size:1.5rem;">
        <span id="themeIcon" class="bi bi-moon"></span>
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
            <li class="nav-item">
                <a href="users.php" class="nav-link"><i class="bi bi-people"></i><span>Users</span></a>
            </li>
            <li class="nav-item">
                <a href="../index.php" class="nav-link"><i class="bi bi-graph-up"></i><span>Analytics</span></a>
            </li>
        </ul>
    </div>

    <div class="main-content">
        <?php if (!empty($_SESSION['error_message'])): ?>
        <div class="alert alert-danger text-center">
            <?= htmlspecialchars($_SESSION['error_message']) ?>
        </div>
        <?php unset($_SESSION['error_message']); endif; ?>

        <div class="dashboard-header">
            <h3>Task</h3>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTaskModal">
                <i class="bi bi-plus-lg"></i> Add Task
            </button>
        </div>

        <div class="student-list">
            <table class="student-table">
                <thead>
                <tr>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Due Date</th>
                    <th>Assigned To</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($tasks as $task): ?>
                    <tr>
                        <td><?= htmlspecialchars($task['title']) ?></td>
                        <td><?= htmlspecialchars($task['description']) ?></td>
                        <td><?= date('m/d/Y H:i', strtotime($task['due_date'])) ?></td>
                        <td><?= $task['assigned_to'] ? htmlspecialchars($task['first_name'] . ' ' . $task['last_name']) : 'Unassigned' ?></td>
                        <td>
                            <?php
                            $statusClass = match($task['status']) {
                                'completed' => 'success',
                                'pending' => 'warning',
                                'submitted' => 'info',
                                default => 'secondary'
                            };
                            ?>
                            <span class="badge bg-<?= $statusClass ?>">
                                <?= ucfirst($task['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($task['submission_id'])): ?>
                                <button
                                    class="btn btn-sm btn-info view-submission-btn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#viewSubmissionModal"
                                    data-task-id="<?= $task['task_id'] ?>"
                                    data-student-name="<?= htmlspecialchars($task['first_name'] . ' ' . $task['last_name']) ?>"
                                    data-file-path="<?= htmlspecialchars($task['file_path']) ?>"
                                    data-submitted-at="<?= htmlspecialchars($task['submitted_at']) ?>"
                                    data-status="<?= htmlspecialchars($task['status']) ?>"
                                >
                                    View Submission
                                </button>
                                <?php if ($task['status'] !== 'completed'): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="task_id" value="<?= $task['task_id'] ?>">
                                    <button type="submit" name="mark_completed" class="btn btn-sm btn-success">
                                        Mark Completed
                                    </button>
                                </form>
                                <?php endif; ?>
                            <?php else: ?>
                                <button class="btn btn-sm btn-secondary" disabled>No Submission</button>
                            <?php endif; ?>

                            <button 
                                class="btn btn-sm btn-primary edit-task-btn"
                                data-bs-toggle="modal"
                                data-bs-target="#editTaskModal"
                                data-task-id="<?= $task['task_id'] ?>"
                                data-title="<?= htmlspecialchars($task['title']) ?>"
                                data-description="<?= htmlspecialchars($task['description']) ?>"
                                data-due-date="<?= date('Y-m-d\TH:i', strtotime($task['due_date'])) ?>"
                                data-assigned-to="<?= $task['assigned_to'] ?>"
                            >
                                Edit
                            </button>
                            <form method="POST" class="d-inline delete-task-form">
                                <input type="hidden" name="task_id" value="<?= $task['task_id'] ?>">
                                <button type="submit" name="delete_task" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Task Modal -->
<div class="modal fade" id="addTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" name="title" required />
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Due Date</label>
                        <input type="datetime-local" class="form-control" name="due_date" required />
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assign To (multiple)</label>
                        <select class="form-control" name="assigned_to[]" multiple size="5" required>
                            <?php foreach ($students as $student): ?>
                                <option value="<?= $student['student_id'] ?>">
                                    <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Hold Ctrl (Cmd on Mac) to select multiple students</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" name="add_task">Save Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Submission Modal -->
<div class="modal fade" id="viewSubmissionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">View Submission</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="submission-details">
                    <p><strong>Student:</strong> <span id="submissionStudentName"></span></p>
                    <p><strong>Submitted:</strong> <span id="submissionDate"></span></p>
                    <p><strong>Status:</strong> <span id="submissionStatus"></span></p>
                    <div id="submissionFileSection">
                        <p><strong>Attached File:</strong> <a id="submissionFileLink" href="#" target="_blank">Download</a></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Task Modal -->
<div class="modal fade" id="editTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="task_id" id="editTaskId">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" name="title" id="editTaskTitle" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="editTaskDescription" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Due Date</label>
                        <input type="datetime-local" class="form-control" name="due_date" id="editTaskDueDate" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assign To</label>
                        <select class="form-control" name="assigned_to" id="editTaskAssignedTo" required>
                            <?php foreach ($students as $student): ?>
                                <option value="<?= $student['student_id'] ?>">
                                    <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_task" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    // View Submission Modal
    $('.view-submission-btn').click(function() {
        const studentName = $(this).data('student-name');
        const submittedAt = new Date($(this).data('submitted-at')).toLocaleString();
        const status = $(this).data('status');
        const filePath = $(this).data('file-path');

        $('#submissionStudentName').text(studentName);
        $('#submissionDate').text(submittedAt);
        $('#submissionStatus').text(status);
        
        if (filePath) {
            $('#submissionFileSection').show();
            $('#submissionFileLink').attr('href', '../' + filePath);
        } else {
            $('#submissionFileSection').hide();
        }
    });

    // Edit Task Modal
    $('.edit-task-btn').click(function() {
        const taskId = $(this).data('task-id');
        const title = $(this).data('title');
        const description = $(this).data('description');
        const dueDate = $(this).data('due-date');
        const assignedTo = $(this).data('assigned-to');

        $('#editTaskId').val(taskId);
        $('#editTaskTitle').val(title);
        $('#editTaskDescription').val(description);
        $('#editTaskDueDate').val(dueDate);
        $('#editTaskAssignedTo').val(assignedTo);
    });

    // Delete Task Confirmation
    $('.delete-task-form').submit(function(e) {
        if (!confirm('Are you sure you want to delete this task?')) {
            e.preventDefault();
        }
    });
});

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
