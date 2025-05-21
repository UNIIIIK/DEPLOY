<?php 
require 'dbconnection.php';
include('admin/auth_check.php');

// Determine if user is admin
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'];

if (!$isAdmin) {
    header("Location: login.php");
    exit();
}

// Fetch admin user details
$adminId = $_SESSION['admin'];
$stmt = $connection->prepare("SELECT * FROM admin_users WHERE id = ?");
$stmt->execute([$adminId]);
$adminUser = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch other necessary data
$students = $connection->query("SELECT * FROM students")->fetchAll(PDO::FETCH_ASSOC);
$verifiedUsers = $connection->query("SELECT COUNT(*) FROM students WHERE is_verified = 1")->fetchColumn();
$activeSessions = 1;
$systemHealth = 100;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
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
                    <a href="admin/task.php" class="nav-link">
                        <i class="bi bi-list-task"></i>
                        <span>Tasks</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="admin/users.php" class="nav-link">
                        <i class="bi bi-people"></i>
                        <span>Users</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link">
                        <i class="bi bi-graph-up"></i>
                        <span>Analytics</span>
                    </a>
                </li>
                
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
                        <div class="stat-value"><?= $verifiedUsers ?></div>
                        <div class="stat-label">Verified Users</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?= $activeSessions ?></div>
                        <div class="stat-label">Active Sessions</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-heart-pulse"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?= $systemHealth ?>%</div>
                        <div class="stat-label">System Health</div>
                    </div>
                </div>
            </div>

            <!-- Student List -->
            <div class="student-list">
                <div class="section-header">
                    <h5>Student List</h5>
                </div>
                <table class="student-table">
                    <thead>
                        <tr>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Course</th>
                            <th>Address</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?= htmlspecialchars($student['first_name']) ?></td>
                            <td><?= htmlspecialchars($student['last_name']) ?></td>
                            <td><?= htmlspecialchars($student['course']) ?></td>
                            <td><?= htmlspecialchars($student['user_address']) ?></td>
                            <td>
                                <span class="status-badge <?= $student['is_verified'] ? 'verified' : 'not-verified' ?>">
                                    <?= $student['is_verified'] ? 'Verified' : 'Not Verified' ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- User Panel with Profile and Logout -->
    <div class="user-panel-container">
        <div class="user-panel" id="userProfileBtn">
            <img src="profiles/default_admin.png" class="user-avatar">
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($adminUser['full_name']) ?></div>
            </div>
        </div>
        <a href="admin/logout.php" class="logout-btn">
    <i class="bi bi-box-arrow-right"></i> Logout
        </a>
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
            $(this).hide();
            $('#saveProfile').show();
            $('#cancelEdit').show();
        });

        // Cancel edit button click handler
        $('#cancelEdit').click(function() {
            $('#editProfileForm').hide();
            $('#viewProfile').show();
            $('#editProfileBtn').show();
            $('#saveProfile').hide();
            $(this).hide();
        });

        // Profile image preview
        $('#profileImage').change(function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#profileImagePreview').attr('src', e.target.result);
                }
                reader.readAsDataURL(file);
            }
        });

        // Save profile
        $('#saveProfile').click(function() {
            const formData = new FormData();
            formData.append('user_id', <?= $adminUser['id'] ?>);
            formData.append('first_name', $('#firstName').val());
            formData.append('last_name', $('#lastName').val());
            formData.append('email', $('#email').val());
            formData.append('gender', '<?= $adminUser['gender'] ?>');
            formData.append('address', $('#address').val());
            formData.append('birthdate', $('#birthdate').val());
            formData.append('course', $('#course').val());
            
            const profileImage = $('#profileImage')[0].files[0];
            if (profileImage) {
                formData.append('profileImage', profileImage);
            }

            $.ajax({
                url: 'update_user.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    const data = typeof response === 'string' ? JSON.parse(response) : response;
                    if (data.status === "success") {
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Update failed'));
                    }
                },
                error: function(xhr) {
                    alert('Error: ' + xhr.responseText);
                }
            });
        });

     
      
    });
    </script>
    <script>
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