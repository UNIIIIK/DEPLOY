<?php 
require 'dbconnection.php';
include('admin/auth_check.php');

// Create profiles directory if it doesn't exist
$profilesDir = __DIR__ . '/profiles';
if (!file_exists($profilesDir)) {
    mkdir($profilesDir, 0777, true);
}

// Determine if user is a student
$isStudent = isset($_SESSION['is_student']) && $_SESSION['is_student'];

if (!$isStudent) {
    header("Location: admin/login.php");
    exit();
}

// Fetch student user details
$studentId = $_SESSION['student'];
$stmt = $connection->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->execute([$studentId]);
$studentUser = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch pending tasks assigned to the student
$pendingTasks = $connection->prepare("
    SELECT t.*, ts.submission_id, ts.submission_type, ts.submitted_at 
    FROM tasks t 
    LEFT JOIN task_submissions ts ON t.task_id = ts.task_id 
    WHERE t.assigned_to = ? AND t.status != 'completed'
    ORDER BY t.due_date ASC
");
$pendingTasks->execute([$studentId]);
$pendingTasks = $pendingTasks->fetchAll(PDO::FETCH_ASSOC);

// Fetch completed tasks assigned to the student
$completedTasks = $connection->prepare("
    SELECT t.*, ts.submission_id, ts.submission_type, ts.submitted_at 
    FROM tasks t 
    LEFT JOIN task_submissions ts ON t.task_id = ts.task_id 
    WHERE t.assigned_to = ? AND t.status = 'completed'
    ORDER BY t.due_date DESC
");
$completedTasks->execute([$studentId]);
$completedTasks = $completedTasks->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
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
                    <a href="student_dashboard.php" class="nav-link active">
                        <i class="bi bi-list-task"></i>
                        <span>Tasks</span>
                    </a>
                </li>
               
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Dashboard Header -->
            <div class="dashboard-header">
                <h3>Dashboard</h3>
            </div>

            <!-- Stats Cards -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-person-check"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?= $studentUser['is_verified'] ? 'Verified' : 'Not Verified' ?></div>
                        <div class="stat-label">Status</div>
                    </div>
                </div>
            </div>

            <!-- Task Lists -->
            <div class="task-section">
                <h5>Pending Tasks</h5>
                <?php if (count($pendingTasks) > 0): ?>
                <div class="student-list">
                <table class="student-table">
                    <thead>
                        <tr class="task">
                            <th>Title</th>
                            <th>Description</th>
                            <th>Deadline</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingTasks as $task): ?>
                        <tr>
                            <td><?= htmlspecialchars($task['title']) ?></td>
                            <td><?= htmlspecialchars($task['description']) ?></td>
                            <td><?= date('m/d/Y H:i', strtotime($task['due_date'])) ?></td>
                            <td>
                                <?php
                                $statusClass = match($task['status']) {
                                    'completed' => 'completed',
                                    'submitted' => 'submitted',
                                    default => 'pending'
                                };
                                ?>
                                <span class="status-badge <?= $statusClass ?>">
                                    <?= ucfirst($task['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (empty($task['submission_id'])): ?>
                                    <button class="btn btn-sm btn-primary submit-task" data-id="<?= $task['task_id'] ?>">Submit</button>
                                <?php else: ?>
                                    <a class="btn btn-sm btn-info view-submission" data-id="<?= $task['submission_id'] ?>">View Submission</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php else: ?>
                <p>No pending tasks found.</p>
                <?php endif; ?>
            </div>

            <div class="task-section">
                <h5>Completed Tasks</h5>
                <?php if (count($completedTasks) > 0): ?>
                <div class="student-list">
                <table class="student-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Deadline</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($completedTasks as $task): ?>
                        <tr>
                            <td><?= htmlspecialchars($task['title']) ?></td>
                            <td><?= htmlspecialchars($task['description']) ?></td>
                            <td><?= date('m/d/Y H:i', strtotime($task['due_date'])) ?></td>
                            <td>
                             <?php
                                $statusClass = match(strtolower($task['status'])) {
                                    'submitted' => 'submitted',
                                    'completed' => 'completed',
                                    default => 'pending'
                                };
                                ?>
                                <span class="status-badge <?= $statusClass ?>">
                                    <?= ucfirst($task['status']) ?>
                                    </span>

                            </td>
                            <td>
                                <?php if ($task['status'] === 'submitted'): ?>
                                    <a class="btn btn-sm btn-primary view-submission" data-id="<?= $task['submission_id'] ?>">View</a>

                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php else: ?>
                <p>No completed tasks found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- User Panel with Profile -->
    <div class="user-panel-container">
        <div class="user-panel" id="userProfileBtn">
            <?php
            $profileImage = $studentUser['profile_image'];
            $profileImagePath = $profileImage ? "profiles/{$profileImage}" : "assets/images/default.png";
            if ($profileImage && !file_exists($profileImagePath)) {
                $profileImagePath = "assets/images/default.png";
            }
            ?>
            <img src="<?= htmlspecialchars($profileImagePath) ?>" class="user-avatar">
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($studentUser['first_name'] . ' ' . $studentUser['last_name']) ?></div>
                <div class="user-role">Student</div>
            </div>
        </div>
        <a href="admin/logout.php" class="logout-btn">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>

    <!-- Student Profile Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content quantum-modal">
                <div class="modal-header quantum-modal-header">
                    <h5 class="modal-title quantum-gradient-text">Student Profile</h5>
                    <button type="button" class="btn-close quantum-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body quantum-modal-body">
                    <div class="profile-image-container text-center mb-4">
                        <img src="<?= htmlspecialchars($profileImagePath) ?>" 
                             alt="Profile" 
                             class="quantum-avatar"
                             id="profileImagePreview">
                    </div>

                    <!-- View Profile Section -->
                    <div id="viewProfile">
                        <div class="quantum-info-grid">
                            <div class="quantum-info-item">
                                <span class="quantum-info-label">First Name</span>
                                <span class="quantum-info-value"><?= htmlspecialchars($studentUser['first_name']) ?></span>
                            </div>
                            <div class="quantum-info-item">
                                <span class="quantum-info-label">Last Name</span>
                                <span class="quantum-info-value"><?= htmlspecialchars($studentUser['last_name']) ?></span>
                            </div>
                            <div class="quantum-info-item">
                                <span class="quantum-info-label">Email</span>
                                <span class="quantum-info-value"><?= htmlspecialchars($studentUser['email']) ?></span>
                            </div>
                            <div class="quantum-info-item">
                                <span class="quantum-info-label">Phone</span>
                                <span class="quantum-info-value"><?= htmlspecialchars($studentUser['phone_number']) ?></span>
                            </div>
                            <div class="quantum-info-item">
                                <span class="quantum-info-label">Course</span>
                                <span class="quantum-info-value"><?= htmlspecialchars($studentUser['course']) ?></span>
                            </div>
                            <div class="quantum-info-item">
                                <span class="quantum-info-label">Gender</span>
                                <span class="quantum-info-value"><?= htmlspecialchars($studentUser['gender']) ?></span>
                            </div>
                            <div class="quantum-info-item">
                                <span class="quantum-info-label">Birthdate</span>
                                <span class="quantum-info-value"><?= htmlspecialchars($studentUser['birthdate']) ?></span>
                            </div>
                            <div class="quantum-info-item">
                                <span class="quantum-info-label">Address</span>
                                <span class="quantum-info-value"><?= htmlspecialchars($studentUser['user_address']) ?></span>
                            </div>
                        </div>
                        <div class="mt-4 text-center">
                            <button type="button" class="quantum-btn quantum-btn-edit" id="editProfileBtn">
                                <i class="bi bi-pencil"></i> Edit Profile
                            </button>
                        </div>
                    </div>

                    <!-- Edit Profile Form -->
                    <form id="editProfileForm" style="display: none;" enctype="multipart/form-data">
                        <input type="hidden" name="student_id" value="<?= $studentUser['student_id'] ?>">
                        <div class="quantum-form-grid">
                            <div class="quantum-form-group">
                                <label class="quantum-input-label">First Name</label>
                                <input type="text" class="quantum-input" name="first_name" value="<?= htmlspecialchars($studentUser['first_name']) ?>">
                            </div>
                            <div class="quantum-form-group">
                                <label class="quantum-input-label">Last Name</label>
                                <input type="text" class="quantum-input" name="last_name" value="<?= htmlspecialchars($studentUser['last_name']) ?>">
                            </div>
                            <div class="quantum-form-group">
                                <label class="quantum-input-label">Email</label>
                                <input type="email" class="quantum-input" name="email" value="<?= htmlspecialchars($studentUser['email']) ?>">
                            </div>
                            <div class="quantum-form-group">
                                <label class="quantum-input-label">Phone Number</label>
                                <input type="text" class="quantum-input" name="phone_number" value="<?= htmlspecialchars($studentUser['phone_number']) ?>">
                            </div>
                            <div class="quantum-form-group">
                                <label class="quantum-input-label">Course</label>
                                <input type="text" class="quantum-input" name="course" value="<?= htmlspecialchars($studentUser['course']) ?>">
                            </div>
                            <div class="quantum-form-group">
                                <label class="quantum-input-label">Gender</label>
                                <select class="quantum-input" name="gender">
                                    <option value="Male" <?= $studentUser['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= $studentUser['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                                    <option value="Other" <?= $studentUser['gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                            <div class="quantum-form-group">
                                <label class="quantum-input-label">Birthdate</label>
                                <input type="date" class="quantum-input" name="birthdate" value="<?= htmlspecialchars($studentUser['birthdate']) ?>">
                            </div>
                            <div class="quantum-form-group">
                                <label class="quantum-input-label">Address</label>
                                <input type="text" class="quantum-input" name="user_address" value="<?= htmlspecialchars($studentUser['user_address']) ?>">
                            </div>
                            <div class="quantum-form-group">
                                <label class="quantum-input-label">Profile Image</label>
                                <div class="quantum-upload-btn">
                                    <i class="bi bi-cloud-upload"></i>
                                    <input type="file" class="quantum-input" name="profile_image" accept="image/*" id="profileImageInput">
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 text-center">
                            <button type="submit" class="quantum-btn quantum-btn-success" id="saveProfile">Save Changes</button>
                            <button type="button" class="quantum-btn quantum-btn-secondary" id="cancelEdit">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Task Submission Modal -->
    <div class="modal fade" id="submitTaskModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Submit Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="taskSubmissionForm" enctype="multipart/form-data">
                        <input type="hidden" name="task_id" id="submitTaskId">
                        <div class="mb-3">
                            <label class="form-label">Submission Notes</label>
                            <textarea class="form-control" name="submission_notes" rows="4" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Attach File (Optional)</label>
                            <input type="file" class="form-control" name="submission_file" accept=".pdf,.doc,.docx,.txt,.zip,.rar,.jpg,.jpeg,.png">
                            <small class="form-text text-muted">Allowed file types: PDF, DOC, DOCX, TXT, ZIP, RAR, JPG, JPEG, PNG</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Submission Type</label>
                            <select class="form-control" name="submission_type">
                                <option value="Me">Individual</option>
                                <option value="Group">Group</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitTaskBtn">Submit</button>
                </div>
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
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6>Task Details</h6>
                                <p><strong>Title:</strong> <span id="submissionTaskTitle"></span></p>
                                <p><strong>Description:</strong> <span id="submissionTaskDescription"></span></p>
                                <p><strong>Due Date:</strong> <span id="submissionDueDate"></span></p>
                            </div>
                            <div class="col-md-6">
                                <h6>Submission Details</h6>
                                <p><strong>Submitted:</strong> <span id="submissionDate"></span></p>
                                <p><strong>Type:</strong> <span id="submissionType"></span></p>
                                <p><strong>Status:</strong> <span id="submissionStatus" class="badge"></span></p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <h6>Submission Notes</h6>
                                <div id="submissionNotes" class="border p-3 mb-3 rounded"></div>
                                <div id="submissionFileSection">
                                    <h6>Attached File</h6>
                                    <div id="submissionFileLink"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        // Show profile modal when clicking user panel
        $('#userProfileBtn').click(function() {
            $('#profileModal').modal('show');
        });

        // Edit button click handler
        $('#editProfileBtn').click(function() {
            $('#viewProfile').hide();
            $('#editProfileForm').show();
        });

        // Cancel edit button click handler
        $('#cancelEdit').click(function() {
            $('#editProfileForm').hide();
            $('#viewProfile').show();
        });

        // Task submission handlers
        $('.submit-task').click(function() {
            const taskId = $(this).data('id');
            $('#submitTaskId').val(taskId);
            $('#submitTaskModal').modal('show');
        });

        $('#submitTaskBtn').click(function() {
            const form = $('#taskSubmissionForm')[0];
            const formData = new FormData(form);

            $.ajax({
                url: 'submit_task.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        alert('Task submitted successfully!');
                        location.reload(); // Reload to show updated status
                    } else {
                        alert('Error: ' + (response.message || 'Failed to submit task'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Submission error:', xhr.responseText);
                    alert('An error occurred while submitting the task. Please try again.');
                }
            });
        });

        // Handle form submission
        $('#editProfileForm').submit(function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            
            // Add required fields for update_user.php
            formData.append('user_id', formData.get('student_id'));
            formData.append('address', formData.get('user_address'));

            // Handle profile image upload properly
            const profileImageInput = $('#profileImageInput')[0];
            if (profileImageInput.files.length > 0) {
                formData.append('profile_image', profileImageInput.files[0]);
            }
            
            // Ensure gender and birthdate are included
            if (!formData.get('gender')) {
                formData.append('gender', '<?= htmlspecialchars($studentUser['gender']) ?>');
            }
            if (!formData.get('birthdate')) {
                formData.append('birthdate', '<?= htmlspecialchars($studentUser['birthdate']) ?>');
            }

            $.ajax({
                url: 'update_user.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.status === "success") {
                        // Fetch updated user data
                        $.ajax({
                            url: 'fetch_user.php',
                            type: 'POST',
                            data: { user_id: formData.get('student_id') },
                            dataType: 'json',
                            success: function(userData) {
                                if (userData) {
                                    // Update user panel
                                    $('.user-info .user-name').text(userData.first_name + ' ' + userData.last_name);
                                    
                                    // Update profile images with timestamp to prevent caching
                                    const timestamp = new Date().getTime();
                                    const profileImageUrl = userData.profile_image ? 
                                        'profiles/' + userData.profile_image + '?t=' + timestamp : 
                                        'assets/images/default.png';
                                    
                                    $('.user-avatar').attr('src', profileImageUrl);
                                    $('#profileImagePreview').attr('src', profileImageUrl);
                                    
                                    // Update modal view content
                                    $('#viewProfile .quantum-info-value').each(function() {
                                        const field = $(this).prev('.quantum-info-label').text().toLowerCase();
                                        switch(field) {
                                            case 'first name':
                                                $(this).text(userData.first_name);
                                                break;
                                            case 'last name':
                                                $(this).text(userData.last_name);
                                                break;
                                            case 'email':
                                                $(this).text(userData.email);
                                                break;
                                            case 'phone':
                                                $(this).text(userData.phone_number);
                                                break;
                                            case 'course':
                                                $(this).text(userData.course);
                                                break;
                                            case 'address':
                                                $(this).text(userData.user_address);
                                                break;
                                            case 'gender':
                                                $(this).text(userData.gender);
                                                break;
                                            case 'birthdate':
                                                $(this).text(userData.birthdate);
                                                break;
                                        }
                                    });

                                    // Update form fields
                                    $('#editProfileForm input[name="first_name"]').val(userData.first_name);
                                    $('#editProfileForm input[name="last_name"]').val(userData.last_name);
                                    $('#editProfileForm input[name="email"]').val(userData.email);
                                    $('#editProfileForm input[name="phone_number"]').val(userData.phone_number);
                                    $('#editProfileForm input[name="course"]').val(userData.course);
                                    $('#editProfileForm input[name="user_address"]').val(userData.user_address);
                                    $('#editProfileForm select[name="gender"]').val(userData.gender);
                                    $('#editProfileForm input[name="birthdate"]').val(userData.birthdate);

                                    alert('Profile updated successfully!');
                                    $('#editProfileForm').hide();
                                    $('#viewProfile').show();
                                }
                            },
                            error: function() {
                                console.error('Error fetching updated user data');
                                location.reload();
                            }
                        });
                    } else {
                        alert('Error: ' + (response.message || 'Failed to update profile'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Update error:', xhr.responseText);
                    alert('An error occurred while updating the profile. Please try again.');
                }
            });
        });

        // Profile image preview
        $('#profileImageInput').change(function() {
            if (this.files && this.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#profileImagePreview').attr('src', e.target.result);
                }
                reader.readAsDataURL(this.files[0]);
            }
        });

        // Update the view submission link to use modal
        $(document).on('click', '.view-submission', function(e) {
            e.preventDefault();
            const submissionId = $(this).data('id');
            
            // Fetch submission details
            $.ajax({
                url: 'get_submission.php',
                type: 'POST',
                data: { submission_id: submissionId },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        const submission = response.data;
                        
                        // Update modal content
                        $('#submissionTaskTitle').text(submission.task.title);
                        $('#submissionTaskDescription').text(submission.task.description);
                        $('#submissionDueDate').text(new Date(submission.task.due_date).toLocaleString());
                        $('#submissionDate').text(new Date(submission.submission.submitted_at).toLocaleString());
                        $('#submissionType').text(submission.submission.type);
                        $('#submissionNotes').text(submission.submission.text);
                        
                        // Update status badge
                        const statusBadge = $('#submissionStatus');
                        statusBadge.text(submission.task.status);
                        statusBadge.removeClass().addClass('badge');
                        switch(submission.task.status.toLowerCase()) {
                            case 'submitted':
                                statusBadge.addClass('bg-info');
                                break;
                            case 'completed':
                                statusBadge.addClass('bg-success');
                                break;
                            default:
                                statusBadge.addClass('bg-warning');
                        }
                        
                        // Handle file attachment
                        const fileSection = $('#submissionFileSection');
                        const fileLink = $('#submissionFileLink');
                        if (submission.submission.file_path) {
                            fileSection.show();
                            fileLink.html(`<a href="${submission.submission.file_path}" target="_blank" class="btn btn-sm btn-primary">
                                <i class="bi bi-download"></i> Download File
                            </a>`);
                        } else {
                            fileSection.hide();
                        }
                        
                        // Show modal
                        $('#viewSubmissionModal').modal('show');
                    } else {
                        alert('Error: ' + (response.message || 'Failed to load submission details'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching submission:', xhr.responseText);
                    alert('An error occurred while loading the submission details. Please try again.');
                }
            });
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