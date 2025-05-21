<?php
require 'dbconnection.php';
include('admin/auth_check.php');

header('Content-Type: application/json');

// Ensure student is logged in
if (!isset($_SESSION['is_student']) || !$_SESSION['is_student']) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['submission_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit();
}

$submissionId = filter_input(INPUT_POST, 'submission_id', FILTER_VALIDATE_INT);
$studentId = $_SESSION['student'];

try {
    // Fetch submission details with task information
    $stmt = $connection->prepare("
        SELECT 
            ts.*,
            t.title,
            t.description,
            t.due_date,
            t.status as task_status
        FROM task_submissions ts
        JOIN tasks t ON ts.task_id = t.task_id
        WHERE ts.submission_id = ? AND ts.student_id = ?
    ");
    $stmt->execute([$submissionId, $studentId]);
    $submission = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$submission) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Submission not found']);
        exit();
    }

    // Format response
    $response = [
        'status' => 'success',
        'data' => [
            'task' => [
                'title' => $submission['title'],
                'description' => $submission['description'],
                'due_date' => $submission['due_date'],
                'status' => $submission['task_status']
            ],
            'submission' => [
                'text' => $submission['submission_text'],
                'file_path' => $submission['file_path'],
                'type' => $submission['submission_type'],
                'submitted_at' => $submission['submitted_at']
            ]
        ]
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error',
        'details' => $e->getMessage()
    ]);
}
?> 