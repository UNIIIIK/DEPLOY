<?php
session_start();
require 'dbconnection.php';

header('Content-Type: application/json');

// Enable detailed error reporting (for dev)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ensure student login
if (!isset($_SESSION['is_student']) || !$_SESSION['is_student'] || !isset($_SESSION['student'])) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access - Please log in as student'
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method - Only POST allowed'
    ]);
    exit();
}

// Get inputs
$taskId = filter_input(INPUT_POST, 'task_id', FILTER_VALIDATE_INT);
$submissionNotes = trim($_POST['submission_notes'] ?? '');
$submissionType = $_POST['submission_type'] ?? 'Me';
$studentId = intval($_SESSION['student']);

// Basic validation
if (!$taskId || $taskId <= 0) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid task ID'
    ]);
    exit();
}

if (empty($submissionNotes)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Please enter submission notes.'
    ]);
    exit();
}

try {
    // Confirm task belongs to this student
    $stmt = $connection->prepare("SELECT task_id FROM tasks WHERE task_id = ? AND assigned_to = ? LIMIT 1");
    $stmt->execute([$taskId, $studentId]);

    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Task not found or not assigned to you'
        ]);
        exit();
    }

    // File handling
    $filePath = null;
    if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/submissions/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileInfo = pathinfo($_FILES['submission_file']['name']);
        $extension = strtolower($fileInfo['extension'] ?? '');
        $allowed = ['pdf', 'doc', 'docx', 'txt', 'zip', 'rar', 'jpg', 'jpeg', 'png'];

        if (!in_array($extension, $allowed)) {
            http_response_code(415);
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid file type. Allowed: ' . implode(', ', $allowed)
            ]);
            exit();
        }

        $fileName = "submission_{$taskId}_{$studentId}_" . bin2hex(random_bytes(6)) . ".$extension";
        $targetPath = $uploadDir . $fileName;

        if (!move_uploaded_file($_FILES['submission_file']['tmp_name'], $targetPath)) {
            throw new Exception('File upload failed');
        }

        $filePath = 'submissions/' . $fileName;
    } elseif (!empty($_FILES['submission_file']['error']) && $_FILES['submission_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'File upload error: ' . $_FILES['submission_file']['error']
        ]);
        exit();
    }

    // DB Insert
    $connection->beginTransaction();

    try {
        // Insert submission
        $stmt = $connection->prepare("
            INSERT INTO task_submissions (task_id, student_id, submission_text, file_path, submission_type, submitted_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$taskId, $studentId, $submissionNotes, $filePath, $submissionType]);

        // Update task status to submitted
        $stmt = $connection->prepare("UPDATE tasks SET status = 'submitted' WHERE task_id = ?");
        $stmt->execute([$taskId]);

        $connection->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'Task submitted successfully'
        ]);
    } catch (Exception $e) {
        $connection->rollBack();
        if (isset($filePath) && file_exists($filePath)) {
            unlink($filePath);
        }
        throw $e;
    }
} catch (PDOException $e) {
    $connection->rollBack();
    if ($filePath && file_exists(__DIR__ . '/' . $filePath)) {
        unlink(__DIR__ . '/' . $filePath);
    }
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error',
        'details' => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
