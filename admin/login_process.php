<?php
session_start();
require __DIR__ . '/../dbconnection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        // Check admin users
        $stmtAdmin = $connection->prepare("SELECT * FROM admin_users WHERE username = ?");
        $stmtAdmin->execute([$username]);
        $adminUser = $stmtAdmin->fetch(PDO::FETCH_ASSOC);

        // Check students
        $stmtStudent = $connection->prepare("SELECT * FROM students WHERE email = ? AND is_verified = 1");
        $stmtStudent->execute([$username]);
        $studentUser = $stmtStudent->fetch(PDO::FETCH_ASSOC);

        // Verify admin credentials with password_verify
        if ($adminUser) {
            if (password_verify($password, $adminUser['password'])) {
                $_SESSION['admin'] = $adminUser['id'];
                $_SESSION['is_admin'] = true;
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Admin login successful',
                    'is_admin' => true
                ]);
                exit();
            } else {
                // Password didn't match
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid admin password'
                ]);
                exit();
            }
        }
        // Verify student credentials
        elseif ($studentUser && password_verify($password, $studentUser['user_password'])) {
    $_SESSION['student'] = $studentUser['student_id'];
    $_SESSION['is_student'] = true;
    $_SESSION['student_email'] = $studentUser['email']; // Optional but useful
    echo json_encode([
        'status' => 'success',
        'message' => 'Student login successful',
        'is_admin' => false
    ]);
    exit();
}
        else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid username or password'
            ]);
            exit();
        }
    } catch (PDOException $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error: ' . $e->getMessage()
        ]);
        exit();
    }
}

echo json_encode([
    'status' => 'error',
    'message' => 'Invalid request method'
]);
?>