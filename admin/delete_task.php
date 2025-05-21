<?php
require __DIR__ . '/../dbconnection.php';

header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_id'])) {
    $taskId = $_POST['task_id'];

    try {
        $stmt = $connection->prepare("DELETE FROM tasks WHERE task_id = ?");
        if ($stmt->execute([$taskId])) {
            $response['status'] = 'success';
            $response['message'] = 'Task deleted successfully';
        } else {
            $response['message'] = 'Failed to delete task';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request';
}

echo json_encode($response);
?>